import { kvGet, kvSet } from './db'

const KEY = 'cache:perfil:v1'

export async function getCachedPerfil() {
	return kvGet(KEY)
}

export async function setCachedPerfil(perfil) {
	return kvSet(KEY, {
		data: JSON.parse(
			JSON.stringify(perfil, (_, value) => {
							// Eliminar funciones y referencias no serializables
							if (typeof value === 'function') return undefined
							return value
			})
		),
		cachedAt: new Date().toISOString()
	})
}
