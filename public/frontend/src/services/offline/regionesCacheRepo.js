import { kvGet, kvSet } from './db'

const KEY = 'cache:regiones:v1'

export async function getCachedRegiones() {
	return kvGet(KEY)
}

export async function setCachedRegiones(regiones) {
	return kvSet(KEY, {
		data: JSON.parse(
			JSON.stringify(regiones, (_, value) => {
				if (typeof value === 'function') return undefined
				return value
			})
		),
		cachedAt: new Date().toISOString()
	})
}
