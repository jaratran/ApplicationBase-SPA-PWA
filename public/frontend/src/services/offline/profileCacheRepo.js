import { kvGet, kvSet } from './db'

const KEY = 'cache:perfil:v1'

export async function getCachedPerfil() {
	return kvGet(KEY)
}

export async function setCachedPerfil(perfil) {
	return kvSet(KEY, {
		data: perfil,
		cachedAt: new Date().toISOString()
	})
}
