import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '../stores/auth'                           // Usa el Pinia store para autenticar

// 🧩 Importar vistas y layouts
import GuestLayout from '../layouts/GuestLayout.vue'
import LoginView from '../views/LoginView.vue'
import DashboardView from '../views/DashboardView.vue'

// 🗺️ Definición de rutas
const routes = [
  { path: '/', redirect: '/login' },

  // 🧱 Login envuelto en GuestLayout
  {
    path: '/login',
    component: GuestLayout, // Envolvente visual (logo + fondo)
    children: [
      {
        path: '',
        name: 'login',
        component: LoginView, // Contenido dentro del layout
      },
    ],
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
