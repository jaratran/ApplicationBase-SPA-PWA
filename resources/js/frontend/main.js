import { createApp } from 'vue'

import { createPinia } from 'pinia'                                                 // manejo de estado
import router from './src/router'                                                   // navegación
import App from './src/App.vue'                                                     // App principal

import axios from 'axios'                                                           // AXIOS

import { applyDesignParameters } from './src/utils/applyDesignParameters'           // consulta tu API para obtener parámetros de diseño

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
    app.mount('#app')
})
