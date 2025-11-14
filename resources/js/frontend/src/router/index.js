// resources/js/frontend/src/router/index.js
import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '../stores/auth'                           // Usa el Pinia store para autenticar

// 🧩 Importar vistas y layouts
import LoginView from '../views/LoginView.vue'
import DashboardView from '../views/DashboardView.vue'

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
