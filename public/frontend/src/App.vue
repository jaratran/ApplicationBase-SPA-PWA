<template>
	<component :is="layoutComponent">
		<router-view />
	</component>
</template>

<script setup>
	import { computed, onMounted, onUnmounted } from 'vue'
	import { useRoute } from 'vue-router'
	import { useNetworkStore } from './stores/network'

	import GuestLayout from './layouts/GuestLayout.vue'
	import AuthenticatedLayout from './layouts/AuthenticatedLayout.vue'

	import { applyDesignParameters } from './utils/applyDesignParameters'				// consulta tu API para obtener parámetros de diseño

	// -----------------------------------------------------------------------------
	// Conectividad: Inicializar listeners globales para manejar el estado de la conexión
	// -----------------------------------------------------------------------------
	const networkStore = useNetworkStore()

	function onOnline() {
		console.log('[network] online')
		networkStore.setOnline()
	}

	function onOffline() {
		console.log('[network] offline')
		networkStore.setOffline()
	}

	// -----------------------------------------------------------------------------
	// Layout dinámico según contexto (invitado / autenticado)
	// -----------------------------------------------------------------------------
	const route = useRoute()
	const layoutComponent = computed(() => {
		return route.meta.requiresAuth
			? AuthenticatedLayout
			: GuestLayout
	})

	// -----------------------------------------------------------------------------
	// Listener de mensajes del SW, responde parámetros de diseño persistidos
	// -----------------------------------------------------------------------------
	function onServiceWorkerMessage(event) {
		if (event.data?.type !== 'REQUEST_DESIGN_PARAMS') return

		const raw = localStorage.getItem('designParameters')
		if (!raw) return

		try {
			event.source?.postMessage({	type: 'SEND_DESIGN_PARAMS',
										payload: JSON.parse(raw),
									})
		} catch {
			// No romper la app si el storage está corrupto
		}
	}

	// -----------------------------------------------------------------------------
	// Bootstrap funcional global de la SPA
	// -----------------------------------------------------------------------------
	onMounted(async () => {
		// 0) Registrar listener globales para manejar el estado de la conexión
		addEventListener('online', onOnline)
		addEventListener('offline', onOffline)

		if (navigator.onLine) {			// Evitamos estado “por defecto”
			onOnline()
		} else {
			onOffline()
		}

		// 1) Registrar listener del Service Worker (una sola vez)
		if (navigator.serviceWorker) {
			navigator.serviceWorker.addEventListener('message',
				onServiceWorkerMessage
			)
		}

		// 2) Aplicar parámetros de diseño (online u offline)
		try {
			await applyDesignParameters()
		} catch {
			console.warn('[design] Design parameters not available (offline)')
		} finally {
			document.body.classList.remove('booting')							// UX: cuando terminen quitar estado de boot global
		}
	})

	onUnmounted(() => {
		// Limpiamos Listeners
		removeEventListener('online', onOnline)
		removeEventListener('offline', onOffline)

		// Limpiamos Service Workers
		if (navigator.serviceWorker) {
			navigator.serviceWorker.removeEventListener(	'message',
															onServiceWorkerMessage
			)
		}
	})
</script>
