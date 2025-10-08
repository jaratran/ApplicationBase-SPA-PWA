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
            await api.get('/sanctum/csrf-cookie')
        },

        async login(email, password) {
            this.loading = true
            this.error = null
            try {
                await this.getCsrfCookie()
                const response = await api.post('/api/login', { email, password })
                this.user = response.data.user
                router.push('/dashboard')
            } catch (error) {
                this.error = 'Credenciales inválidas o error de conexión'
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
            await api.post('/api/logout')
            this.user = null
            router.push('/login')
        }
    }
})