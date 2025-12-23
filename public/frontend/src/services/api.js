import axios from 'axios'

// Configuración global de Axios para Sanctum
const api = axios.create({
  baseURL: '/api',
  withCredentials: true, // 🔹 Necesario para cookies de sesión Sanctum
  headers: {
    Accept: 'application/json',
    // ⚠️ NO definir Content-Type - 'Content-Type': 'application/json'
  }
})

// Configuración global de Axios para Sanctum y CSRF
const sanctum = axios.create({
  baseURL: '/sanctum',
  withCredentials: true,
  headers: {
    Accept: 'application/json',
    // ⚠️ NO definir Content-Type - 'Content-Type': 'application/json'
  }
})

// Interceptor: captura respuestas 401 y lanza evento global
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
export { sanctum }
