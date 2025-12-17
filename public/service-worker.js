// ==================================================================================
// Service Worker — Calidad PWA
// Mejoras:
// 			Precarga automática de archivos que deben precachearse,
// 			favicon offline, manejo seguro de errores, robustez API design-parameters
// 			y fallback SPA estable.
// ==================================================================================

// Nombre del caché (cambiará en cada despliegue)
const CACHE_NAME = "Calidad-v80";

const APP_SHELL = "/build/index.html";

/**
 * --------------------------------------------------------------------------
 * Precarga automática del manifest de Vite para el Service Worker
 * --------------------------------------------------------------------------
 * El archivo "manifest-sw.js" es generado automáticamente por el script:
 *      scripts/generate-sw-manifest.js
 * después de cada build de Vite.
 *
 * Dicho archivo expone un arreglo global:
 *      self.__PRECACHE = [ ... ];
 * que contiene archivos esenciales:
 *   - El shell base de la SPA ("/")
 *   - Íconos y recursos esenciales de la PWA
 *   - Todos los bundles JS/CSS generados por Vite, incluidos los que poseen
 *     nombres con hash dinámico y los importados por otros módulos.
 *
 * Al cargarlo aquí mediante importScripts(), el Service Worker obtiene la
 * lista completa y actualizada de archivos que deben precachearse. Luego,
 * ASSETS_TO_CACHE utiliza ese arreglo para garantizar que el SW instale
 * siempre los assets correctos sin necesidad de mantener esta lista a mano.
 */
importScripts("/build/manifest-sw.js");
const ASSETS_TO_CACHE = self.__PRECACHE;

// // + cache automático de assets desde fetch
// const ASSETS_TO_CACHE = [
// 							'/build/index.html',
// 							'/manifest.json',

// 							'/config/default_emblema.png',
// 							'/config/default_fondo.png',
// 							'/config/default_logo.png',
// 							'/config/default_favicon.png'
// 						]

/**
 * --------------------------------------------------------------------------
 * Cache dedicado para parámetros de diseño dinámicos
 * --------------------------------------------------------------------------
 * API_CACHE se utiliza para almacenar en caché las respuestas de la API:
 *      /api/design-parameters
 *
 * A diferencia del precache tradicional (ASSETS_TO_CACHE), que incluye solo
 * archivos estáticos del build, este cache almacena datos JSON variables que
 * la aplicación necesita incluso cuando está offline:
 *   - Colores dinámicos del tema
 *   - Logos e íconos personalizados
 *   - Parámetros visuales configurables por el administrador
 *
 * Se maneja como un cache separado para:
 *   - Mantener independencia entre assets estáticos y datos dinámicos
 *   - Permitir invalidación selectiva sin afectar el precache completo
 *   - Asegurar que la SPA pueda reconstruir su apariencia aún sin conexión
 *
 * El SW usará este cache en las estrategias de fetch para permitir que la
 * aplicación cargue el diseño incluso en modo offline.
 */
const API_CACHE = "api-design-parameters";

// Resolver para comunicación con cliente durante install y poder rescatar parámetros de diseño almacenados en el localStorage
let pendingDesignParamsResolver = null;
// Listener GLOBAL (Chrome ya no dará warning)
self.addEventListener("message", (event) => {
	if (
		pendingDesignParamsResolver &&
		event.data &&
		event.data.type === "SEND_DESIGN_PARAMS"
	) {
		pendingDesignParamsResolver(event.data.payload);
		pendingDesignParamsResolver = null; // limpiar
	}
});

// ------------------------------------------------
// INSTALACIÓN del Service Worker
// ------------------------------------------------
self.addEventListener("install", (event) => {
	console.log("[ServiceWorker] Install", CACHE_NAME);

	event.waitUntil(
		(async () => {
			// 1) Precarga básica de assets del build
			try {
				const cache = await caches.open(CACHE_NAME);
				await cache.addAll(ASSETS_TO_CACHE);
			} catch (_) { }
		})()
	);
	// Activar inmediatamente, sin esperar reload
	self.skipWaiting();
});

// ------------------------------------------------
// ACTIVACIÓN del Service Worker
// ------------------------------------------------
self.addEventListener("activate", (event) => {
	console.log("[ServiceWorker] Activate", CACHE_NAME);

	event.waitUntil(
		caches.keys().then(keys =>
			Promise.all(
				keys
					.filter(key => key !== CACHE_NAME && key !== API_CACHE)
					.map(key => caches.delete(key))
			)
		)
	);

	self.clients.claim();
});

// ------------------------------------------------
// FETCH - Servir siempre el spa para navegación
// ------------------------------------------------
self.addEventListener("fetch", (event) => {
	const req = event.request;
	const url = new URL(req.url);

	// ------------------------------
	// 0) Ignorar recursos de terceros (Google Fonts, CDNJS, etc.) - Evita errores de consola causados por SW
	// ------------------------------
	if (url.origin !== self.location.origin) {
		return; // Dejamos que el navegador falle tranquilo si está offline
	}

	// ------------------------------
	// 1) ¿Es navegación (document)? → devolver siempre el App Shell
	// ------------------------------
	if (req.mode === "navigate") {
		event.respondWith(
			(async () => {
				const cache = await caches.open(CACHE_NAME);

				// 1️⃣ Intentar resolver SIEMPRE el app-shell cacheado
				const cachedShell = await cache.match(APP_SHELL);
				if (cachedShell) {
					return cachedShell;
				}

				// 2️⃣ Fallback a red (solo primer load online)
				try {
					const networkResp = await fetch(APP_SHELL);
					if (networkResp.ok) {
						await cache.put(APP_SHELL, networkResp.clone());
					}
					return networkResp;

				} catch {
					return new Response(
						"<!DOCTYPE html><html><body><h1>Offline</h1><p>No se pudo cargar la aplicación.</p></body></html>",
						{ headers: { "Content-Type": "text/html" }, status: 503 }
					);
				}
			})()
		);
		return;
	}

	// ------------------------------
	// 2) Imágenes dinámicas de estilo y favicon
	// ------------------------------
	if (url.pathname.startsWith("/config/")) {
		event.respondWith(
			caches.open(CACHE_NAME).then(async cache => {

				const cached = await cache.match(req);

				// A) Network-first con actualización de cache
				const networkFetch = fetch(req)
					.then(response => {
						if (response.ok && response.status === 200) {
							cache.put(req, response.clone());
						}
						return response;
					})
					.catch(() => null);

				// B) Si hay cache → devolverlo inmediatamente
				if (cached) {
					networkFetch.then(() => { });
					return cached;
				}

				// C) Si no hay cache → intentar red
				const net = await networkFetch;
				if (net) return net;

				// Fallback semántico (SIEMPRE disponible por precache)
				if (url.pathname.includes("emblema"))
					return cache.match("/config/default_emblema.png");

				if (url.pathname.includes("fondo"))
					return cache.match("/config/default_fondo.png");

				if (url.pathname.includes("favicon"))
					return cache.match("/config/default_favicon.png");

				return cache.match("/config/default_logo.png");
			})
		);

		return; // IMPORTANTE
	}

	// ------------------------------
	// 3) API design-parameters (EXCEPCIÓN de CACHEAR API)
	// ------------------------------
	if (url.pathname.startsWith("/api/design-parameters")) {
		const cleanUrl = "/api/design-parameters";

		event.respondWith(
			// Intentamos pedirlo a la red primero (online)
			fetch(req)
				.then(response => {
					// Guardamos la copia en cache dinámica
					const cloned = response.clone();
					caches.open(API_CACHE).then(cache => {
						cache.put(cleanUrl, cloned);
					});
					return response;
				})
				.catch(async () => {
					// Si no hay red → devolvemos la última versión guardada
					const cached = await caches.match(cleanUrl);
					if (cached) return cached;

					return new Response(
						JSON.stringify({ error: "offline" }),
						{ status: 200, headers: { "Content-Type": "application/json" } }
					);
				})
		);

		return; // No seguir procesando este request
	}

	// ------------------------------
	// 4) Ignorar todas las APIs normales - No cachear otras APIs
	// ------------------------------
	if (url.pathname.startsWith("/api/")) {
		return;
	}

	// ------------------------------
	// 5) Para Assets locales (JS/CSS/img) estáticos del mismo origen
	// ------------------------------
	event.respondWith(
		(async () => {
			const cached = await caches.match(req);
			if (cached) return cached;

			try {
				return await fetch(req);
			} catch {
				return new Response("", { status: 404 });
			}
		})()
	);

});
