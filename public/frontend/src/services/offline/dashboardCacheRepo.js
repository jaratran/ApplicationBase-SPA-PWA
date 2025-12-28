import { kvGet, kvSet } from './db'

const KEY = 'cache:dashboard:v1'

export async function getCachedDashboard() {
	return kvGet(KEY)
}

export async function setCachedDashboard(data) {
	return kvSet(KEY, {
		data,
		cachedAt: new Date().toISOString()
	})
}
