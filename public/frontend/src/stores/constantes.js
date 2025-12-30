import { defineStore } from 'pinia'
import { useNetworkStore } from './network'
import { getCachedConstantes, setCachedConstantes } from '@/services/offline/constantesCacheRepo'

import api from '../services/api'

export const useConstantesStore = defineStore('constantes', {
	state: () => ({
		data: null,
		loaded: false,
	}),

	actions: {
		async fetchConstantes() {
			if (this.loaded && this.data) return this.data

			const network = useNetworkStore()

			// 🔌 OFFLINE → cache
			if (!network.isOnline) {
				const cached = await getCachedConstantes()
				if (cached?.data) {
					this.data = cached.data
					this.loaded = true
					return this.data
				}
				return null
			}

			// 🌐 ONLINE → API
			const response = await api.get('/constantes')

			this.data = response.data
			this.loaded = true

			await setCachedConstantes(this.data)

			return this.data
		},
	},

	getters: {
		roleLabelById: (state) => (id) => {
			if (!state.data) return null

			// Buscar la clave cuyo valor coincida con el id
			const entry = Object.entries(state.data)
				.find(([key, value]) => key.startsWith('ROL_') && value === id)

			if (!entry) return null

			// Convertir ROL_SOLICITANTE_PLANTA → Solicitante Planta
			return entry[0]
				.replace(/^ROL_/, '')
				.replace(/_/g, ' ')
				.toLowerCase()
				.replace(/\b\w/g, l => l.toUpperCase())
		}
	}

})
