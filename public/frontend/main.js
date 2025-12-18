// CSS GLOBAL DEL SISTEMA
import './css/bootstrap-sim.css'
import './css/app.css'
import './css/auth.css'

import { createApp } from 'vue'

import axios from 'axios'                                                           // AXIOS
import { createPinia } from 'pinia'                                                 // manejo de estado
import { useNetworkStore } from './src/stores/network'								// Manejo del estado de conectividad
import router from './src/router'                                                   // navegación front para PWA

import AlertSystem from './src/components/AlertSystem.vue'							// componente global para alertas del sistema con estilo de alerts de EcoRuta

import { useOfflineIdentityStore } from './src/stores/offlineIdentity'				// Persistencia mínima de la identidad del usuario (offline-enabled)
import { applyDesignParameters } from './src/utils/applyDesignParameters'           // consulta tu API para obtener parámetros de diseño

import App from './src/App.vue'                                                     // Monta App principal

import '@fortawesome/fontawesome-free/css/all.min.css'                              // Font Awesome (para el ojo)

// Configuración global Axios
axios.defaults.baseURL = import.meta.env.VITE_API_BASE_URL							// Configurar Axios en main.js
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest'				// Configurar Axios globalmente con el token CSRF

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

// 🔹 Luego, en background:
applyDesignParameters().catch(() => {
	console.warn('Design parameters not available (offline)')
})
// Y cuando terminen los design parameters:
applyDesignParameters().finally(() => {
	document.body.classList.remove('booting')
})

// 🔹 Registro del Service Worker (Permitir registro también en DEV para pruebas)
if ('serviceWorker' in navigator) {
	navigator.serviceWorker.register('/service-worker.js')
		.then(() => console.log("✔ Service Worker registrado"))
		.catch(err => console.warn("⚠ Error registrando Service Worker:", err));
}

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
