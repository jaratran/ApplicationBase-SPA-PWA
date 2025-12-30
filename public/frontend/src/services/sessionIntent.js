/**
 * sessionIntent.js
 * --------------------------------------------------
 * Maneja la intención explícita del usuario
 * respecto a su sesión local.
 *
 * Permite distinguir:
 * - Cierre real de sesión
 * - Salida de la app sin cerrar sesión
 */

const KEY = 'session_intent'

export function setSessionIntent(intent) {
	// intent: 'keep' | 'logout'
	try {
		localStorage.setItem(KEY, intent)
	} catch { }
}

export function getSessionIntent() {
	try {
		return localStorage.getItem(KEY)
	} catch {
		return null
	}
}

export function clearSessionIntent() {
	try {
		localStorage.removeItem(KEY)
	} catch { }
}
