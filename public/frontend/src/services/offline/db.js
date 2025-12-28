import { openDB } from 'idb'

const DB_NAME = 'calidad-pwa'
const DB_VERSION = 1

export const dbPromise = openDB(DB_NAME, DB_VERSION, {
	upgrade(db) {
		// Key-Value store simple (perfil, dashboard, etc.)
		if (!db.objectStoreNames.contains('kv')) {
			db.createObjectStore('kv')
		}

		// Outbox para operaciones offline
		if (!db.objectStoreNames.contains('outbox')) {
			const store = db.createObjectStore('outbox', {
				keyPath: 'id',
				autoIncrement: true
			})
			store.createIndex('by_status', 'status')
			store.createIndex('by_createdAt', 'createdAt')
		}
	}
})

export async function kvGet(key) {
	const db = await dbPromise
	return db.get('kv', key)
}

export async function kvSet(key, value) {
	const db = await dbPromise
	return db.put('kv', value, key)
}

export async function kvDel(key) {
	const db = await dbPromise
	return db.delete('kv', key)
}
