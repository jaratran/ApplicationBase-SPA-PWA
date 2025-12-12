// ==================================================================================
// Service Worker — Calidad PWA
// Mejoras:
// 			Precarga automática de archivos que deben precachearse,
// 			favicon offline, manejo seguro de errores, robustez API design-parameters
// 			y fallback SPA estable.
// ==================================================================================

// Nombre del caché (cambiará en cada despliegue)
const CACHE_NAME = "Calidad-v44";

// ⭐ Clave interna para cachear la APP_SHELL de la SPA (página resources\views\frontend.blade.php)
const APP_SHELL = "/__app_shell__";

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

				// ⭐ NUEVO PASO: precachear el HTML shell REAL de la SPA
				try {
					const resp = await fetch("/", { cache: "no-store" }); // fetch("/") obtiene el HTML REAL, no usamos: Request("/", { cache: "reload" })

					if (resp && resp.ok) {
						await cache.put(APP_SHELL, resp.clone());
						console.log("✔ App Shell precacheado como", APP_SHELL);
					} else {
						console.warn("⚠ No se pudo obtener el App Shell desde la red");
					}
				} catch (e) {
					console.warn("⚠ Error precacheando App Shell:", e);
				}

			} catch (_) { }

			// 2) Intentar absorber parámetros de diseño desde el cliente activo
			try {
				const allClients = await self.clients.matchAll({ includeUncontrolled: true });

				if (allClients && allClients.length > 0) {
					// Enviamos solicitud
					const params = await new Promise((resolve) => {
						pendingDesignParamsResolver = resolve;
						allClients[0].postMessage({ type: "REQUEST_DESIGN_PARAMS" });
					});

					// Si recibimos datos → guardarlos en API_CACHE
					if (params) {
						const apiCache = await caches.open(API_CACHE);
						await apiCache.put(
							"/api/design-parameters",
							new Response(JSON.stringify(params), {
								headers: { "Content-Type": "application/json" }
							})
						);
						console.log("✔ SW absorbió parámetros iniciales desde localStorage.");
					}
				}
			} catch (e) {
				console.warn("⚠ No fue posible absorber parámetros iniciales:", e);
			}
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
	// 0) ¿Es navegación (document)? → devolver siempre el App Shell
	// ------------------------------
	if (req.mode === "navigate") {
		event.respondWith(
			(async () => {
				const cache = await caches.open(CACHE_NAME);

				// Intentar devolver el App Shell desde cache
				const shell = await cache.match(APP_SHELL);
				if (shell) return shell;

				// Si no existe en cache → intentar red
				try {
					const networkResp = await fetch(req);
					return networkResp;
				} catch (e) {
					// Fallback final
					return new Response(
						"<h1>Offline</h1><p>No se pudo cargar la aplicación.</p>",
						{ headers: { "Content-Type": "text/html" }, status: 503 }
					);
				}
			})()
		);
		return;
	}

	// ------------------------------
	// 1) FAVICON offline
	// ------------------------------
	if (url.pathname === "/favicon.ico") {
		event.respondWith(
			caches.match("/config/default_favicon.png")
		);
		return;
	}

	// ------------------------------
	// 2) API design-parameters (EXCEPCIÓN de CACHEAR API)
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
	// 3) Ignorar todas las APIs normales - No cachear otras APIs
	// ------------------------------
	if (url.pathname.startsWith("/api/")) {
		return;
	}

	// ------------------------------
	// 4) Ignorar recursos de terceros (Google Fonts, CDNJS, etc.) - Evita errores de consola causados por SW
	// ------------------------------
	if (url.origin !== self.location.origin) {
		return; // Dejamos que el navegador falle tranquilo si está offline
	}

	// ------------------------------
	// 5) Fallback para imágenes dinámicas no cacheadas
	// ------------------------------
	if (url.pathname.startsWith("/config/")) {
		event.respondWith(
			caches.open(CACHE_NAME).then(async cache => {

				const cached = await cache.match(req);

				// A) Intentar actualizar desde red (si hay conexión)						 - DESCOMENTAR ACA
				// const networkFetch = fetch(req)
				// 	.then(response => {
				// 		if (response.ok && response.status === 200) {
				// 			cache.put(req, response.clone());
				// 		}
				// 		return response;
				// 	})
				// 	.catch(() => null);

				// Si hay cache → devolverlo inmediatamente
				if (cached) {
					// B) Mientras tanto actualizamos en background						 	- DESCOMENTAR ACA
					// networkFetch.then(() => { });
					return cached;
				}

				// C) Si no hay cache → esperar red o fallback						 		- DESCOMENTAR ACA
				// const net = await networkFetch;
				// if (net) return net;

				// Fallbacks
				if (url.pathname.includes("emblema"))
					return cache.match("/config/default_emblema.png");

				if (url.pathname.includes("fondo"))
					return cache.match("/config/default_fondo.png");

				if (url.pathname.includes("favicon"))
					return cache.match("/config/default_favicon.png");

				if (url.pathname.includes("logo"))
					return cache.match("/config/default_logo.png");

				return cache.match("/config/default_logo.png");
			})
		);

		return; // IMPORTANTE
	}

	// ------------------------------
	// 6) Para Assets locales (JS/CSS/img) estáticos del mismo origen
	// ------------------------------
	event.respondWith(
		caches.match(req).then(async cached => {
			if (cached) return cached;

			try {
				const networkResp = await fetch(req);
				return networkResp;

			} catch (e) {
				// Si es asset de build que no existe en cache,
				// devolvemos el shell para asegurar la carga del SPA
				if (url.pathname.startsWith("/build/")) {
					return caches.match("/");
				}

				// fallback genérico
				return caches.match("/");
			}
		})
	);

});
