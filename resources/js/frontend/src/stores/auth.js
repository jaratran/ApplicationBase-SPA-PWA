import { defineStore } from 'pinia'
import api, { sanctum } from '../services/api'
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
        await sanctum.get('/csrf-cookie')

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
        const response = await api.post('/login', { email, password })

        // ⚙️ Ajuste: algunos backends no retornan el usuario directamente
        // por eso siempre lo solicitamos explícitamente
        const { data } = await api.get('/user')
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
        const response = await api.get('/user')
        this.user = response.data
      } catch {
        this.user = null
      }
    },

    async logout() {
      this.loading = true
      try {
        await api.post('/logout')
      } catch (error) {
        console.warn('Error al cerrar sesión (ignorado):', error)
      } finally {
        this.user = null
        this.loading = false
        router.push('/login')
      }
    },

    async fetchPerfil() {
      try {
        const { data } = await api.get('/perfil')
        this.user = data.data
        return this.user
      } catch (error) {
        console.error('Error cargando perfil:', error)
        return null
      }
    }
  }
})
