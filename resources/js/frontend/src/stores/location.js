// resources/js/frontend/src/stores/location.js

import { defineStore } from 'pinia'
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
		const { data } = await api.get(`/regiones/${regionId}/comunas`)
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
