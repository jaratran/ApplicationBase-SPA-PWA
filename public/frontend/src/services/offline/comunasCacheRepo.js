import { kvGet, kvSet } from './db'

function key(regionId) {
	return `cache:comunas:region:${regionId}:v1`
}

export async function getCachedComunas(regionId) {
	return kvGet(key(regionId))
}

export async function setCachedComunas(regionId, comunas) {
	return kvSet(key(regionId), {
		data: JSON.parse(JSON.stringify(comunas)),
		cachedAt: new Date().toISOString()
	})
}
