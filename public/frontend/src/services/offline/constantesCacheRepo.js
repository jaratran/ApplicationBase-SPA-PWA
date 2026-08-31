import { kvGet, kvSet } from './db'

const KEY = 'cache:constantes:v2'

export async function getCachedConstantes() {
	return kvGet(KEY)
}

export async function setCachedConstantes(data) {
	return kvSet(KEY, {
		data: JSON.parse(JSON.stringify(data)),
		cachedAt: new Date().toISOString()
	})
}
