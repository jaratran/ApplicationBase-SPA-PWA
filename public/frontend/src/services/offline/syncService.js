import api, { ensureCsrfCookie, isAuthError } from '@/services/api'
import {
	listPendingOutbox,
	markProcessing,
	markDone,
	markFailed
} from './outboxRepo'

async function dispatch(item) {
	switch (item.type) {
		case 'perfil.update':
			return api.post('/perfil/update', item.payload)

		default:
			throw new Error(`Tipo de outbox no soportado: ${item.type}`)
	}
}

export async function processOutboxOnce({ maxItems = 20 } = {}) {
	// Revalidar sesión / CSRF
	await ensureCsrfCookie()

	const items = await listPendingOutbox(maxItems)

	for (const item of items) {
		try {
			await markProcessing(item.id)
			await dispatch(item)
			await markDone(item.id)

		} catch (error) {
			await markFailed(item.id, error)

			// 🔴 Si es error de auth, no seguir procesando
			if (isAuthError(error)) {
				console.warn('[sync] Error de autenticación, sync detenido')
				break
			}
		}
	}
}
