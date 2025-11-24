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
        // -1) Sesión
        await this.getCsrfCookie()

        // 0) Login
        await api.post('/login', { email, password })

        // 1) Usuario básico (equivalente a Auth::user())
        await this.fetchUser()

        // 2) Perfil extendido completo (con avatar)
        await this.fetchPerfil()

        // 3) Enrutamos al Panel de Control
        router.push('/dashboard')

      } catch (error) {
        console.error('Error en login:', error)
        this.error = 'Credenciales inválidas o error de conexión.'
        this.user = null
        this.perfil = null

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
      } catch (error) {
        console.warn('Error al cerrar sesión (ignorado):', error)
      } finally {
        this.user = null
        this.perfil = null
        this.loading = false
        router.push('/login')
      }
    },
  }
})
