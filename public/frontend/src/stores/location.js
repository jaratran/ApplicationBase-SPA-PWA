import { defineStore } from 'pinia'
import { getCachedComunas, setCachedComunas } from '@/services/offline/comunasCacheRepo'
import { useNetworkStore } from './network'
import api from '../services/api'
import { ref } from 'vue'

export const useLocationStore = defineStore('location', () => {

	const regiones = ref([])
	const comunas = ref([])

	async function fetchRegiones() {
		if (regiones.value.length) return regiones.value  // cache

		const { data } = await api.get('/regiones')
		regiones.value = data
		return data
	}

	async function fetchComunas(regionId) {
		const network = useNetworkStore()

		// 🔌 OFFLINE → cache
		if (!network.isOnline) {
			const cached = await getCachedComunas(regionId)
			return cached?.data ?? []
		}

		// 🌐 ONLINE → API
		const { data } = await api.get(`/regiones/${regionId}/comunas`)

		await setCachedComunas(regionId, data)

		comunas.value = data
		return data
	}

	return {
		regiones,
		comunas,
		fetchRegiones,
		fetchComunas,
	}
})
