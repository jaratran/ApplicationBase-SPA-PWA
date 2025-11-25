// resources/js/frontend/src/stores/alert.js

import { defineStore } from 'pinia'
import { ref } from 'vue'

export const useAlertStore = defineStore('alert', () => {
	const message = ref(null)
	const type = ref('success')
	const pending = ref(false)

	function show(msg, t = 'success') {
		message.value = msg
		type.value = t
		pending.value = true
	}

	function prepare() {
		if (pending.value) {			// Mostrarlo una sola vez
			pending.value = false

		} else {						// Y si no viene pendiente, limpiar
			message.value = null
			pending.value = false
		}
	}

	return { message, type, show, prepare }
})
