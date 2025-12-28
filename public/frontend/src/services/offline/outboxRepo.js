import { dbPromise } from './db'

export async function enqueueOutbox(item) {
	const db = await dbPromise

	return db.add('outbox', {
		type: item.type,                       // 'perfil.update'
		payload: item.payload,
		status: 'pending',                     // pending | processing | done | failed
		attempts: 0,
		lastError: null,
		createdAt: new Date().toISOString(),
		updatedAt: new Date().toISOString()
	})
}

export async function listPendingOutbox(limit = 20) {
	const db = await dbPromise
	const all = await db.getAll('outbox')

	return all
		.filter(i => i.status === 'pending' || i.status === 'failed')
		.sort((a, b) => a.createdAt.localeCompare(b.createdAt))
		.slice(0, limit)
}

export async function markProcessing(id) {
	const db = await dbPromise
	const item = await db.get('outbox', id)
	if (!item) return

	item.status = 'processing'
	item.updatedAt = new Date().toISOString()
	await db.put('outbox', item)
}

export async function markDone(id) {
	const db = await dbPromise
	const item = await db.get('outbox', id)
	if (!item) return

	item.status = 'done'
	item.updatedAt = new Date().toISOString()
	await db.put('outbox', item)
}

export async function markFailed(id, error) {
	const db = await dbPromise
	const item = await db.get('outbox', id)
	if (!item) return

	item.status = 'failed'
	item.attempts += 1
	item.lastError = String(error).slice(0, 500)
	item.updatedAt = new Date().toISOString()
	await db.put('outbox', item)
}
