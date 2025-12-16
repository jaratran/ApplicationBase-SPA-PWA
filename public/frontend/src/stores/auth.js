// resources/js/frontend/src/stores/auth.js

import { defineStore } from 'pinia'
import api, { sanctum } from '../services/api'
import router from '../router'

export const useAuthStore = defineStore('auth', {
	state: () => ({
		user: null,        // usuario básico (Auth::user())
		perfil: null,      // perfil extendido (PerfilController)
		loading: false,
		error: null
	}),

	actions: {
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
			try {
				const { data } = await api.get('/perfil')
				this.perfil = data.data
				return this.perfil

			} catch (error) {
				console.error('Error cargando perfil extendido:', error)
				this.perfil = null
				return null
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
				this.user = null
				this.perfil = null
				this.loading = false
			}
		},
	}
})
