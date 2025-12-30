import { defineStore } from 'pinia'

const STORAGE_KEY = 'offline_identity'

export const useOfflineIdentityStore = defineStore('offlineIdentity', {
	state: () => ({
		initialized: false,
		user: null
	}),

	getters: {
		hasIdentity: (state) => !!state.user,								// Existe identidad persistida (independiente del modo)
		isEnabled: (state) => !!state.user?.offline_enabled,				// Identidad habilitada explícitamente para offline
		lastSyncAt: (state) => state.user?.last_sync_at || null,			// Última sincronización conocida
		canResumeSession: (state) => !!state.user?.offline_enabled
											&& !!state.user?.user_id,		// La sesión puede reanudarse sin login explícito (online u offline)
	},

	actions: {
		// Carga identidad persistida desde storage (solo una vez por ciclo de app)
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
				this.user = null
			}

			this.initialized = true
		},

		// Habilita identidad offline desde un user válido (se llama solo tras login ONLINE exitoso)
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

		// Limpia identidad persistida (logout real)
		clear() {
			this.user = null
			localStorage.removeItem(STORAGE_KEY)
		},

		// Actualiza timestamp de última sincronización
		updateSyncTime() {
			if (!this.user) return

			this.user.last_sync_at = new Date().toISOString()
			localStorage.setItem(STORAGE_KEY, JSON.stringify(this.user))
		},

		// NO borra identidad, solo desactiva sesión activa
		softClear() {
			if (!this.user) return

			this.user.last_sync_at = new Date().toISOString()
			localStorage.setItem(STORAGE_KEY, JSON.stringify(this.user))
		}
	}
})
