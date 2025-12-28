import { kvGet, kvSet } from './db'

const KEY = 'cache:dashboard:v1'

export async function getCachedDashboard() {
	return kvGet(KEY)
}

export async function setCachedDashboard(data) {
	return kvSet(KEY, {
		data: JSON.parse(
			JSON.stringify(data, (_, value) => {
							// Eliminar funciones y referencias no serializables
							if (typeof value === 'function') return undefined
							return value
			})
		),
		cachedAt: new Date().toISOString()
	})
}
