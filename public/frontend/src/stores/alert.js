import { defineStore } from 'pinia'
import { ref } from 'vue'

export const useAlertStore = defineStore('alert', () => {
	const type = ref('success')       // success | error | warning | info
	const messages = ref([])          // siempre un array (lo que sea)
	const pending = ref(false)

	/** Recibe string, array o string con saltos de línea */
	function show(msg, t = 'success', persist = false) {				// persist = false → alerta válida solo en esta vista
		if (Array.isArray(msg)) {
			messages.value = msg.flatMap(m => m.split('\n'))
		} else {
			messages.value = msg.split('\n')
		}

		type.value = t
		pending.value = persist											// persist = true → debe persistir para la siguiente vista (verse allá)
	}

	function prepare() {
		if (pending.value) {			// Mostrarlo una sola vez
			pending.value = false

		} else {						// Y si no viene pendiente, limpiar
			type.value = 'success'
			messages.value = []
			pending.value = false
		}
	}

	return { messages, type, show, prepare }
})
