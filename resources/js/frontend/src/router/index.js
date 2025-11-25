// resources/js/frontend/src/router/index.js
import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '../stores/auth'                           // Usa el Pinia store para autenticar

// 🧩 Importar vistas y layouts
import LoginView from '../views/LoginView.vue'
import DashboardView from '../views/DashboardView.vue'

import PerfilView from '../views/Perfil/PerfilView.vue'
import PerfilEditarView from '../views/Perfil/PerfilEditarView.vue'
// import PerfilPasswordView from '../views/Perfil/PerfilPasswordView.vue'

// 🗺️ Definición de rutas
const routes = [
  { path: '/', redirect: '/login' },

  // 🧱 Login (NO requiere autenticación)
  {
    path: '/login',
    name: 'login',
    component: LoginView,
    meta: { requiresAuth: false },
  },

  // 🧭 Dashboard (requiere autenticación)
  {
    path: '/dashboard',
    name: 'dashboard',
    component: DashboardView,
    meta: { requiresAuth: true },
  },

  // 👤 Show del Perfil del Usuario
  {
    path: '/perfil',
    name: 'perfil',
    component: PerfilView,
    meta: { requiresAuth: true }
  },

  // 👤 Edit del Perfil del Usuario
  {
    path: '/perfil/editar',
    name: 'perfil.editar',
    component: PerfilEditarView,
    meta: { requiresAuth: true }
  },

  // // 👤 Cambio de Contraseña del Perfil del Usuario
  // {
  //   path: '/perfil/password',
  //   name: 'perfil.password',
  //   component: PerfilPasswordView,
  //   meta: { requiresAuth: true }
  // },

  // 🚧 Ruta comodín: redirige a login
  { path: '/:pathMatch(.*)*', redirect: '/login' },
]

// ⚙️ Instancia del router
const router = createRouter({
  history: createWebHistory(),
  routes,
})

// 🔒 Protección de rutas
router.beforeEach(async (to) => {
  const auth = useAuthStore()
  if (to.meta.requiresAuth && !auth.user) {
    await auth.fetchUser()
    if (!auth.user) return '/login'
  }
})

export default router
