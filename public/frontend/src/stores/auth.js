import { defineStore } from 'pinia'
import api, { sanctum } from '@/services/api'
import { useLocationStore } from '@/stores/location'
import { useNetworkStore } from './network'
import { useOfflineIdentityStore } from './offlineIdentity'
import { getCachedPerfil, setCachedPerfil } from '@/services/offline/profileCacheRepo'
import { enqueueOutbox } from '@/services/offline/outboxRepo'

export const useAuthStore = defineStore('auth', {
	state: () => ({
		user: null,        // usuario básico (Auth::user())
		perfil: null,      // perfil extendido (PerfilController)
		loading: false,
		error: null
	}),

	actions: {
		/** ==========================
		 *  LOAD OFFLINE CACHE PERFIL
		 *  ========================== */
		async loadPerfilFromCache() {
			try {
				const cached = await getCachedPerfil()
				if (cached?.data) {
					this.perfil = cached.data
					return this.perfil
				}
			} catch (e) {
				console.warn('[auth] No se pudo cargar perfil desde cache', e)
			}

			return null
		},

		/** ==========================
		 *  SAVE OFFLINE CACHE PERFIL
		 *  ========================== */
		async savePerfilToCache(perfil) {
			try {
				await setCachedPerfil(perfil)
			} catch (e) {
				console.warn('[auth] No se pudo guardar perfil en cache', e)
			}
		},

		/** ==========================
		 *  COOKIE CSRF - SESION
		 *  ========================== */
		async getCsrfCookie() {
			try {
				await sanctum.get('/csrf-cookie')

			} catch (error) {
				console.error('Error obteniendo CSRF cookie:', error)
				throw error
			}
		},

		/** ==========================
		 *  LOGIN
		 *  ========================== */
		async login(email, password) {
			const network = useNetworkStore()

			if (!network.isOnline) {
				this.error = 'No hay conexión disponible.'
				return false
			}

			this.loading = true
			this.error = null

			try {
				// 0) Obtener cookie CSRF
				await this.getCsrfCookie()

				// 1) Intentar login
				await api.post('/login', { email, password })

				// 2) Cargar usuario básico y perfil extendido
				const user = await this.fetchUser()
				const perfil = await this.fetchPerfil()

				// Si ambos existen, login exitoso → retornamos true
				if (user && perfil) {
					// Activar modo offline tras login ONLINE
					const offlineIdentity = useOfflineIdentityStore()
					offlineIdentity.enableFromUser(user)

					return true
				}

				// Si por alguna razón falta algo, lo tratamos como error
				this.error = 'No fue posible cargar los datos de usuario.'
				return false

			} catch (error) {
				console.error('Error en login:', error)
				this.error = 'Credenciales inválidas o error de conexión.'
				this.user = null
				this.perfil = null
				return false

			} finally {
				this.loading = false
			}
		},

		/** ==========================
		 *  OBTENER USUARIO BÁSICO
		 *  ========================== */
		async fetchUser() {
			try {
				const { data } = await api.get('/user')
				this.user = data
				return this.user

			} catch (error) {
				console.error('Error cargando usuario básico:', error)
				this.user = null
				return null
			}
		},

		/** ==========================
		 *  PERFIL EXTENDIDO
		 *  ========================== */
		async fetchPerfil() {
			const network = useNetworkStore()

			// 🔌 OFFLINE → cache
			if (!network.isOnline) {
				const cached = await this.loadPerfilFromCache()
				if (cached) return cached

				this.perfil = null
				return null
			}

			// 🌐 ONLINE → API
			try {
				const { data } = await api.get('/perfil')
				this.perfil = data.data

				// Persistir cache offline
				await this.savePerfilToCache(this.perfil)

				return this.perfil

			} catch (error) {
				console.error('Error cargando perfil extendido:', error)

				// Fallback: intentar cache
				const cached = await this.loadPerfilFromCache()
				if (cached) return cached

				this.perfil = null
				return null
			}
		},

		async updatePerfilOfflineFirst(payload) {
			const network = useNetworkStore()

			// 🧠 1) Actualización optimista del store
			const locationStore = useLocationStore()

			let comuna = this.perfil?.comuna
			let region = this.perfil?.region

			// 🔑 Rehidratar proyección usando catálogos locales
			if (payload.comuna_id && locationStore.regiones?.length) {
				for (const r of locationStore.regiones) {
					const comunas = await locationStore.fetchComunas(r.id)
					const found = comunas.find(c => c.id === payload.comuna_id)
					if (found) {
						comuna = found
						region = r
						break
					}
				}
			}

			const actualizado = {
				...(this.perfil ?? {}),
				...payload,
				comuna,
				region,
			}

			this.perfil = actualizado
			await this.savePerfilToCache(actualizado)

			// 🔌 OFFLINE → encolar y salir
			if (!network.isOnline) {
				await enqueueOutbox({
					type: 'perfil.update',
					payload
				})

				return { queued: true }
			}

			// 🌐 ONLINE → intentar backend
			try {
				await api.post('/perfil/update', payload)

				// Rehidratar desde backend (verdad final)
				await this.fetchPerfil()

				return { queued: false }

			} catch (error) {
				console.warn('[auth] Error actualizando perfil, se encola', error)

				const status = error?.response?.status
				if (status === 401 || status === 419) {
					throw error   // dejar que el flujo normal maneje auth
				}

				await enqueueOutbox({
					type: 'perfil.update',
					payload
				})

				return { queued: true }
			}
		},

		/** ==========================
		 *  LOGOUT
		 *  ========================== */
		async logout() {
			this.loading = true

			try {
				await api.post('/logout')
				return true

			} catch (error) {
				console.warn('Error al cerrar sesión (ignorado):', error)
				return false

			} finally {
				const offlineIdentity = useOfflineIdentityStore()
				offlineIdentity.clear()

				this.user = null
				this.perfil = null
				this.loading = false
			}
		},
	}
})
