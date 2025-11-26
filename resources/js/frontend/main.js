// resources/js/frontend/main.js
import { createApp } from 'vue'

import axios from 'axios'                                                           // AXIOS
import { createPinia } from 'pinia'                                                 // manejo de estado
import router from './src/router'                                                   // navegación front para PWA

import AlertSystem from './src/components/AlertSystem.vue'							// componente global para alertas del sistema con estilo de alerts de EcoRuta

import { applyDesignParameters } from './src/utils/applyDesignParameters'           // consulta tu API para obtener parámetros de diseño
import App from './src/App.vue'                                                     // Monta App principal

import '@fortawesome/fontawesome-free/css/all.min.css'                              // Font Awesome (para el ojo)

// Configurar Axios globalmente con el token CSRF
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest'

const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
if (token) {
    axios.defaults.headers.common['X-CSRF-TOKEN'] = token
}

// 🔹 Cargar parámetros antes de montar la app
applyDesignParameters().then(() => {
    const app = createApp(App)
    app.use(createPinia())
	app.use(router)

	app.component('AlertSystem', AlertSystem)										// registrar globalmente el uso de los alertas del sistema como EcoRuta

    app.mount('#app')
})
