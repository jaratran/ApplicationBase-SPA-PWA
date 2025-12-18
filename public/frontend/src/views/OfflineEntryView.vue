<template>
	<div class="min-h-screen flex items-center justify-center bg-gray-50">
		<div class="max-w-md w-full bg-white rounded-lg shadow p-6 text-center">
			<h1 class="text-xl font-semibold mb-2">Modo Offline</h1>

			<p class="text-gray-600 mb-4">
				No hay conexión a internet en este momento.
			</p>

			<div v-if="identity" class="mb-4 text-left bg-gray-100 rounded p-3">
				<p><strong>Usuario:</strong> {{ identity.nombre }}</p>
				<p><strong>Rol:</strong> {{ identity.rol }}</p>
				<p class="text-sm text-gray-500">
					Última sincronización:
					{{ formattedLastSync }}
				</p>
			</div>

			<div class="flex gap-3">
				<button class="flex-1 bg-teal-600 text-white py-2 rounded hover:bg-teal-700" @click="enterOffline">
					Entrar en modo offline
				</button>

				<button class="flex-1 border py-2 rounded" @click="cancel">
					Cancelar
				</button>
			</div>
		</div>
	</div>
</template>

<script setup>
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import { useOfflineIdentityStore } from '@/stores/offlineIdentity'

const router = useRouter()
const offlineIdentity = useOfflineIdentityStore()

offlineIdentity.loadFromStorage()

const identity = computed(() => offlineIdentity.user)

const formattedLastSync = computed(() => {
	if (!identity.value?.last_sync_at) return '—'
	return new Date(identity.value.last_sync_at).toLocaleString()
})

function enterOffline() {
	// Entramos al dashboard con sesión local controlada
	router.replace('/dashboard')
}

function cancel() {
	if (!offlineIdentity.user) {
		router.replace('/login')
	}
}
</script>
