import axios from 'axios'

// =======================================================
// API principal (Laravel API JSON + Sanctum)
// =======================================================
const api = axios.create({
		baseURL: '/api',
		withCredentials: true, // 🔹 Necesario para cookies de sesión Sanctum
		headers: {
			Accept: 'application/json',
			// ⚠️ NO definir Content-Type - 'Content-Type': 'application/json'
		}
	})

// =======================================================
// Cliente Sanctum (CSRF cookie)
// =======================================================
const sanctum = axios.create({
		baseURL: '/sanctum',
		withCredentials: true,
		headers: {
			Accept: 'application/json',
			// ⚠️ NO definir Content-Type - 'Content-Type': 'application/json'
		}
	})

// =======================================================
// Helper: asegurar cookie CSRF (reusable, PWA-safe)
// =======================================================
async function ensureCsrfCookie() {
	try {
		await sanctum.get('/csrf-cookie')
		return true
	} catch (error) {
		console.warn('[api] No se pudo obtener CSRF cookie')
		return false
	}
}

// =======================================================
// Helper: detectar errores de autenticación
// (útil para sync / outbox sin acoplar lógica)
// =======================================================
function isAuthError(error) {
	const status = error?.response?.status
	return status === 401 || status === 419
}

// =======================================================
// Interceptor global de errores (respuestas 401 y lanza evento global)
// =======================================================
api.interceptors.response.use(
		response => response,
		error => {
			if (error.response) {
				if (error.response.status === 401) {
					console.warn('⚠️ Sesión expirada o no autorizada (401).')
					window.dispatchEvent(new CustomEvent('unauthorized'))
				} else {
					console.error(`❌ Error HTTP ${error.response.status}:`, error.response.data)
				}
			} else {
				console.error('❌ Error de red o sin respuesta del servidor:', error)
			}

			return Promise.reject(error)
		}
	)

export default api
export { sanctum, ensureCsrfCookie,	isAuthError }
