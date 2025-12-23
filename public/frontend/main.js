// 1° === CSS GLOBAL / INFRAESTRUCTURA VISUAL ===
import './css/bootstrap-sim.css'													// layout, grid, compatibilidad
import '@fortawesome/fontawesome-free/css/all.min.css'								// iconos (base visual)
import './css/app.css'																// tailwind + variables
import './css/auth.css'																// overrides auth

// 2° === FRAMEWORK BASE ===
import { createApp } from 'vue'														// Instancia de la Aplicación

// 3° === INFRAESTRUCTURA TRANSVERSAL ===
import axios from 'axios'															// interacción con APIS del Backend
import { createPinia } from 'pinia'													// manejo de estado
import router from './src/router'													// navegación front para PWA

// 4° === STORES / UTILIDADES ===
import { useNetworkStore } from './src/stores/network'								// manejo del estado de conectividad
import { useOfflineIdentityStore } from './src/stores/offlineIdentity'				// persistencia mínima de la identidad del usuario (offline-enabled)
import { applyDesignParameters } from './src/utils/applyDesignParameters'			// consulta tu API para obtener parámetros de diseño

// 5° === COMPONENTES GLOBALES ===
import AlertSystem from './src/components/AlertSystem.vue'							// componente global para alertas del sistema con estilo de alerts de EcoRuta

// 6° === APP ROOT ===
import App from './src/App.vue'														// monta App principal

// Configuración global Axios
axios.defaults.baseURL = import.meta.env.VITE_API_BASE_URL							// configurar Axios en main.js
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest'				// configurar Axios globalmente con el token CSRF

// Nuevo patrón (PWA-first) - Vue debe montar SIEMPRE
const app = createApp(App)

const pinia = createPinia()
app.use(pinia)

// Store de red inicializado explícitamente con pinia
const networkStore = useNetworkStore(pinia)

// Persistencia mínima de la identidad del usuario
const offlineIdentity = useOfflineIdentityStore(pinia)
offlineIdentity.loadFromStorage()

// Inicializar listeners globales para manejar el estado de la conexión
window.addEventListener('online', () => {
	console.log('[network] online')
	networkStore.setOnline()
})
window.addEventListener('offline', () => {
	console.log('[network] offline')
	networkStore.setOffline()
})

app.use(router)
app.component('AlertSystem', AlertSystem)										// registrar globalmente el uso de los alertas del sistema como EcoRuta
app.mount('#app')

// // 🔹 Luego, en background:
applyDesignParameters()
	.catch(() => {
		console.warn('Design parameters not available (offline)')				// 🔹 Luego, en background:
	})
	.finally(() => {
		document.body.classList.remove('booting')								// Y cuando terminen los design parameters:
	})

// 🔹 Implementamos atención al mensaje del Service Worker consultando por los parámetros de diseño.
if (navigator.serviceWorker) {
	navigator.serviceWorker.addEventListener("message", (event) => {
		if (event.data && event.data.type === "REQUEST_DESIGN_PARAMS") {
			const params = window.localStorage.getItem('designParameters');
			if (params) {
				event.source.postMessage({
					type: "SEND_DESIGN_PARAMS",
					payload: JSON.parse(params)
				});
			}
		}
	});
}
