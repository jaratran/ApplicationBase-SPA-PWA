import { defineStore } from 'pinia'
import { getCachedRegiones,	setCachedRegiones } from '@/services/offline/regionesCacheRepo'
import { getCachedComunas, setCachedComunas } from '@/services/offline/comunasCacheRepo'
import { useNetworkStore } from './network'
import api from '../services/api'

export const useLocationStore = defineStore('location', {
	state: () => ({
		regiones: [],
		comunasByRegion: {}
	}),

	actions: {
		/* ============================
			* REGIONES (OFFLINE FIRST)
			* ============================ */
		async fetchRegiones() {
			const network = useNetworkStore()

			// 🔌 OFFLINE → cache
			if (!network.isOnline) {
				const cached = await getCachedRegiones()
				this.regiones = cached?.data ?? []
				return this.regiones
			}

			// 🌐 ONLINE → API
			const { data } = await api.get('/regiones')
			this.regiones = data

			await setCachedRegiones(data)
			return data
		},

		/* ============================
			* COMUNAS POR REGIÓN (OFFLINE FIRST)
			* ============================ */
		async fetchComunas(regionId) {
			const network = useNetworkStore()

			// 🔌 OFFLINE → cache
			if (!network.isOnline) {
				const cached = await getCachedComunas(regionId)
				return cached?.data ?? []
			}

			// 🌐 ONLINE → API
			const { data } = await api.get(`/regiones/${regionId}/comunas`)

			await setCachedComunas(regionId, data)
			return data
		}

	}
})
