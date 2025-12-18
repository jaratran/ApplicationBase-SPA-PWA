import { defineStore } from 'pinia'

const STORAGE_KEY = 'offline_identity'

export const useOfflineIdentityStore = defineStore('offlineIdentity', {
	state: () => ({
		initialized: false,
		user: null
	}),

	getters: {
		isEnabled: (state) => !!state.user?.offline_enabled,
		hasIdentity: (state) => !!state.user,
		lastSyncAt: (state) => state.user?.last_sync_at || null
	},

	actions: {
		loadFromStorage() {
			if (this.initialized) return

			try {
				const raw = localStorage.getItem(STORAGE_KEY)
				if (raw) {
					this.user = JSON.parse(raw)
				}
			} catch (e) {
				console.warn('[offlineIdentity] storage corrupto, limpiando')
				localStorage.removeItem(STORAGE_KEY)
			}

			this.initialized = true
		},

		enableFromUser(user) {
			this.user = {
				user_id: user.id,
				nombre: user.nombre_usuario,
				rol: user.rol_id,
				empresa_id: user.empresa_id,
				last_sync_at: new Date().toISOString(),
				offline_enabled: true
			}

			localStorage.setItem(STORAGE_KEY, JSON.stringify(this.user))
		},

		clear() {
			this.user = null
			localStorage.removeItem(STORAGE_KEY)
		},

		updateSyncTime() {
			if (!this.user) return
			this.user.last_sync_at = new Date().toISOString()
			localStorage.setItem(STORAGE_KEY, JSON.stringify(this.user))
		}
	}
})
