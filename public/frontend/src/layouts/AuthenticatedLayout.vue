<template>
	<div class="min-h-screen bg-white flex flex-col">
		<!-- NAVBAR -->
		<Navbar @toggle-sidebar="toggleSidebar" />

		<!-- SIDEMENU flotante -->
		<SideMenu :open="isSidebarOpen" />

		<!-- OVERLAY (escritorio y móvil) -->
		<div v-if="isSidebarOpen" class="fixed inset-0 bg-black/40 z-40" @click="closeSidebar">
		</div>

		<!-- Contenido siempre centrado -->
		<main class="p-4 z-0">
			<!-- Si esta activo 'noPadding' (en router) no se aplica clase que simula container de Bootstrap 5 -->
			<div :class="[$route.meta.noPadding ? '' : 'spa-container']">
				<slot />
			</div>
		</main>
	</div>
</template>

<script setup>
	import { ref, onMounted, onUnmounted } from 'vue'

	import { useAuthStore } from '@/stores/auth'
	import { useConstantesStore } from '@/stores/constantes'
	import { useLocationStore } from '@/stores/location'
	import { useOfflineIdentityStore } from '@/stores/offlineIdentity'
	import { processOutboxOnce } from '@/services/offline/syncService'

	import Navbar from '@/components/Navbar.vue'
	import SideMenu from '@/components/SideMenu.vue'

	const auth = useAuthStore()
	const constantesStore = useConstantesStore()
	const locationStore = useLocationStore()
	const offlineIdentity = useOfflineIdentityStore()

	const isSidebarOpen = ref(false)
	const toggleSidebar = () => isSidebarOpen.value = !isSidebarOpen.value
	const closeSidebar = () => isSidebarOpen.value = false


	onMounted(() => {
		// Contexto autenticado (offline-safe)
		if (!auth.perfil) {
			auth.fetchPerfil()
		}

		// Constantes de dominio (CATOLOGO)
		if (!constantesStore.loaded) {
			constantesStore.fetchConstantes()
		}

		// Regiones / ubicaciones
		if (!locationStore.regiones?.length) {
			locationStore.fetchRegiones()
		}

		// Escuchar reconexión
		window.addEventListener('online', handleBackOnline)
	})

	async function handleBackOnline() {
		try {
			console.log('[offline-sync] Reconectado, procesando cola')

			await processOutboxOnce()

			// Rehidratar perfil desde backend
			await auth.fetchPerfil()

			// Marcar último sync
			offlineIdentity.updateSyncTime()

			console.log('[offline-sync] Sincronización completa')

		} catch (e) {
			console.warn('[offline-sync] Error durante sincronización', e)
		}
	}

	onUnmounted(() => {
		window.removeEventListener('online', handleBackOnline)
	})
</script>

<style scoped>
	/* Replica el mismo padding lateral del <body> en EcoRuta */
	.spa-container {
		padding-left: 2.5rem;
		padding-right: 2.5rem;
	}

	/* Más aire en pantallas grandes (igual que Bootstrap real) */
	@media (min-width: 1200px) {
		.spa-container {
			padding-left: 4rem;
			padding-right: 4rem;
		}
	}
</style>
