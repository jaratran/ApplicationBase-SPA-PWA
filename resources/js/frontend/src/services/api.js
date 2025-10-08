import axios from 'axios'

// Configuración global de Axios para Sanctum
const api = axios.create({
    baseURL: 'https://calidad.hp-notebook.cl', // tu backend Laravel
    withCredentials: true, // 🔹 necesario para cookies Sanctum
    headers: {
        Accept: 'application/json',
        'Content-Type': 'application/json'
    }
})

// Interceptor: captura respuestas 401 y lanza evento global
api.interceptors.response.use(
    response => response,
    error => {
        if (error.response && error.response.status === 401) {
            window.dispatchEvent(new CustomEvent('unauthorized'))
        }
        return Promise.reject(error)
    }
)

export default api