import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '../stores/auth'                           // Usa el Pinia store para autenticar

import { useNetworkStore } from '../stores/network'						// Guard global: decisión ONLINE vs OFFLINE
import { useOfflineIdentityStore } from '../stores/offlineIdentity'

// 🧩 Importar vistas y layouts
import LoginView from '../views/LoginView.vue'
import ForgotPasswordView from '../views/Auth/ForgotPasswordView.vue'
import ResetPasswordView from '../views/Auth/ResetPasswordView.vue'

import DashboardView from '../views/DashboardView.vue'

import PerfilView from '../views/Perfil/PerfilView.vue'
import PerfilEditarView from '../views/Perfil/PerfilEditarView.vue'
import PasswordView from '../views/Perfil/PasswordView.vue'

import OfflineEntryView from '../views/OfflineEntryView.vue'

// 🗺️ Definición de rutas
const routes = [
	{
		path: '/',
		redirect: '/login'
	},

	// 🧱 Login (NO requiere autenticación)
	{
		path: '/login',
		name: 'login',
		component: LoginView,
		meta: { requiresAuth: false },
	},

	// 🧱 Recuperar Contraseña (NO requiere autenticación)
	{
		path: '/forgot-password',
		name: 'forgot-password',
		component: ForgotPasswordView,
		meta: { requiresAuth: false },
	},

	// 🧱 Restablecer Contraseña (NO requiere autenticación)
	{
		path: '/reset-password/:token',
		name: 'reset-password',
		component: ResetPasswordView,
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

	// 👤 Cambio de Contraseña del Perfil del Usuario
	{
		path: '/perfil/password',
		name: 'perfil.password',
		component: PasswordView,
		meta: { requiresAuth: true, noPadding: true } // Agregamos variable que desactiva padding por defecto de las vistas logeadas
	},

	// Flujo de arranque OFFLINE (UI controlada)
	{
		path: '/offline-entry',
		name: 'offline-entry',
		component: OfflineEntryView,
		meta: { requiresAuth: false },
	},

	// 🚧 Ruta comodín: redirige a login
	{
		path: '/:pathMatch(.*)*',
		redirect: '/login'
	},
]

// ⚙️ Instancia del router
const router = createRouter({
	history: createWebHistory(),
	routes,
})

// 🔒 Protección de rutas rehidratando AMBOS: user + perfil y OFFLINE
router.beforeEach(async (to) => {
	const auth = useAuthStore()
	const network = useNetworkStore()
	const offlineIdentity = useOfflineIdentityStore()

	offlineIdentity.loadFromStorage()

	// 🚫 OFFLINE
	if (!network.isOnline) {
		// Si intenta ir a login o ruta pública
		if (!to.meta.requiresAuth) {
			if (!network.isOnline && offlineIdentity.isEnabled) {		// OFFLINE con identidad ...
				if (to.path !== '/offline-entry') {
					return '/offline-entry'								//  → forzar punto de entrada único
				}
			}

			return true
		}

		// Ruta protegida
		if (offlineIdentity.isEnabled) {
			return true
		}

		return '/login'
	}

	// 🌐 ONLINE (comportamiento actual)
	if (to.meta.requiresAuth) {
		if (!auth.user) await auth.fetchUser()
		if (!auth.perfil) await auth.fetchPerfil()
		if (!auth.user) return '/login'
	}
})

export default router
