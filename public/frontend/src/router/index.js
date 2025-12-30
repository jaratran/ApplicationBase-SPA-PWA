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

import SessionEntryView from '../views/SessionEntryView.vue'

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

	// Flujo de arranque OFFLINE (UI controlada) y ONLINE con SESION PREVIA ACTIVA
	{
		path: '/session-entry',
		name: 'session-entry',
		component: SessionEntryView,
		meta: { requiresAuth: false },
	},

	// 🚧 Ruta comodín: redirige a login
	{
		path: '/:pathMatch(.*)*',
		redirect: '/login'
	},
]

let sessionBootstrapped = false

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

	/* =====================================================
	 * BOOTSTRAP ÚNICO DE SESIÓN (online u offline)
	 *
	 * NOTA:
	 * - Soft logout NO destruye identidad
	 * - Hard logout elimina identidad y obliga login
	 * - /session-entry es el punto neutro de reentrada
	 * ===================================================== */
	if (!sessionBootstrapped) {
		sessionBootstrapped = true

		if (offlineIdentity.canResumeSession) {
			const resumed = await auth.resumeFromIdentity()

			// Si logró reanudar y va a login → redirigir
			if (resumed && to.path === '/login') {
				return '/dashboard'
			}
		}
	}

	/* =====================================================
	 * 🚫 OFFLINE
	 * ===================================================== */
	if (!network.isOnline) {
		// Rutas públicas
		if (!to.meta.requiresAuth) {
			if (offlineIdentity.canResumeSession && to.path !== '/session-entry') {
				return '/session-entry'
			}
			return true
		}

		// Rutas protegidas
		if (offlineIdentity.canResumeSession) {
			return true
		}

		return '/login'
	}

	/* =====================================================
	 * ONLINE (comportamiento normal)
	 * ===================================================== */
	if (to.meta.requiresAuth) {
		if (!auth.user) await auth.fetchUser()
		if (!auth.perfil) await auth.fetchPerfil()

		if (!auth.user) return '/login'
	}

	return true
})

export default router
