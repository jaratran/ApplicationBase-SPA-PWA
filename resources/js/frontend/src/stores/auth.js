import { defineStore } from 'pinia'
import api from '../services/api'
import router from '../router'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: null,
    loading: false,
    error: null
  }),

  actions: {
    async getCsrfCookie() {
      try {
        await api.get('/sanctum/csrf-cookie')
      } catch (error) {
        console.error('Error obteniendo CSRF cookie:', error)
        throw error
      }
    },

    async login(email, password) {
      this.loading = true
      this.error = null

      try {
        await this.getCsrfCookie()
        const response = await api.post('/api/login', { email, password })

        // ⚙️ Ajuste: algunos backends no retornan el usuario directamente
        // por eso siempre lo solicitamos explícitamente
        const { data } = await api.get('/api/user')
        this.user = data

        router.push('/dashboard')
      } catch (error) {
        console.error('Error en login:', error)
        this.error = 'Credenciales inválidas o error de conexión.'
        this.user = null
      } finally {
        this.loading = false
      }
    },

    async fetchUser() {
      try {
        const response = await api.get('/api/user')
        this.user = response.data
      } catch {
        this.user = null
      }
    },

    async logout() {
      this.loading = true
      try {
        await api.post('/api/logout')
      } catch (error) {
        console.warn('Error al cerrar sesión (ignorado):', error)
      } finally {
        this.user = null
        this.loading = false
        router.push('/login')
      }
    }
  }
})
