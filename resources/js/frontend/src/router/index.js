import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import LoginView from '../views/LoginView.vue'
import DashboardView from '../views/DashboardView.vue'

const routes = [
    { path: '/', redirect: '/login' },
    { path: '/login', name: 'login', component: LoginView },
    { path: '/dashboard', name: 'dashboard', component: DashboardView, meta: { requiresAuth: true } },
    { path: '/:pathMatch(.*)*', redirect: '/login' }
]

const router = createRouter({
    history: createWebHistory(),
    routes
})

// 🔒 Protección de rutas
router.beforeEach(async (to) => {
    const auth = useAuthStore()
    if (to.meta.requiresAuth && !auth.user) {
        await auth.fetchUser()                      // ⚠️ uso seguro en caso que fetchUser no esté aún implementado
        if (!auth.user) return '/login'
    }
})

export default router