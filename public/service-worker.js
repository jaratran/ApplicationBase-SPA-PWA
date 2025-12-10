// ===============================================
// Service Worker — Calidad PWA
// Mejoras: favicon offline, manejo seguro de errores,
// robustez API design-parameters y fallback SPA estable.
// ===============================================

// Nombre del caché (cambiará en cada despliegue)
const CACHE_NAME = "Calidad-v30";

// Cache de API específica
const API_CACHE = "api-design-parameters";

// Archivos esenciales que se precachean (app shell mínimo)
const ASSETS_TO_CACHE = [
	"/",								// ← Shell REAL
	"/manifest.json",
	"/config/pwa/icon-192.png",
	"/config/pwa/icon-512.png",

	// Precachear los PNG esenciales
	"/config/default_emblema.png",
	"/config/default_favicon.png",
	"/config/default_fondo.png",
	"/config/default_logo.png",

	// build assets (JS / CSS / fuentes)
	"/build/assets/app-9aYweeFC.js",
	"/build/assets/app-CBQG2sON.css",
	"/build/assets/bootstrap-sim-B3Y0FiiP.css",
	"/build/assets/fa-brands-400-BfBXV7Mm.woff2",
	"/build/assets/fa-regular-400-BVHPE7da.woff2",
	"/build/assets/fa-solid-900-8GirhLYJ.woff2",
	"/build/assets/fa-v4compatibility-DnhYSyY-.woff2",
	"/build/assets/index-ngrFHoWO.js",
	"/build/assets/main-CyMOrrXf.js",
	"/build/assets/main-D4sUyk9X.css",
];

// ------------------------------------------------
// INSTALACIÓN del Service Worker
// ------------------------------------------------
self.addEventListener("install", (event) => {
	console.log("[ServiceWorker] Install", CACHE_NAME);

	event.waitUntil(
		caches.open(CACHE_NAME)
			.then(cache => cache.addAll(ASSETS_TO_CACHE))
			.catch(() => null)
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
	// 0) ¿Es navegación (document)? → Devolver SPA shell "/"
	// ------------------------------
	if (req.mode === "navigate") {

		event.respondWith(
			caches.match("/").then(cached => {
				return cached || fetch(req).catch(() => caches.match("/"));
			})
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
