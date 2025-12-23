import { defineStore } from 'pinia'
import api from '../services/api'

export const useConstantesStore = defineStore('constantes', {
	state: () => ({
		data: null,
		loaded: false,
	}),

	actions: {
		async fetchConstantes() {
			if (this.loaded && this.data) return this.data

			const response = await api.get('/constantes')

			this.data = response.data
			this.loaded = true

			return this.data
		},
	},
})
