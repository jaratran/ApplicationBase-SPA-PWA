<template>
	<div class="flex flex-col md:flex-row min-h-screen bg-gray-50 overflow-hidden">
		<!-- Columna izquierda -->
		<div class="w-full md:w-1/3 flex justify-center items-center p-6 md:p-10">
			<div class="bg-white shadow-2xl rounded-2xl p-8 w-full max-w-md">
				<div class="mb-6">
					<!-- Emblema del Sitio -->
					<img :src="getEmblema()" alt="Emblema del Sitio" class="mx-auto max-h-36 object-contain" />
				</div>

				<!-- Título principal -->
				<h2 class="text-xl font-semibold text-center mb-8" :style="{ color: getPrimaryColor() }">
					Iniciar Sesión
				</h2>

				<!-- include Alertas del Sistema -->

				<!-- Formulario -->
				<form @submit.prevent="submitLogin" class="space-y-5">
					<!-- EMAIL -->
					<div>
						<label for="email" class="block text-sm font-medium text-gray-700 mb-1">
							Correo Electrónico
						</label>
						<input id="email" v-model="email" type="email" required autofocus
							class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-nonefocus:ring-2 focus:ring-[var(--bs-primary)] focus:border-[var(--bs-primary)]" />

						<!-- @error('email') -->
					</div>

					<!-- PASSWORD + OJO -->
					<div>
						<label for="password" class="block text-sm font-medium text-gray-700 mb-1">
							Contraseña
						</label>
						<div class="relative">
							<input id="password" v-model="password" :type="showPassword ? 'text' : 'password'" required
								class="w-full border border-gray-300 rounded-lg px-3 py-2 pr-10 focus:outline-none focus:ring-2 focus:ring-[var(--bs-primary)] focus:border-[var(--bs-primary)]" />
							<button type="button" @click="togglePassword"
								class="absolute inset-y-0 right-0 flex items-center px-3 text-gray-500 hover:text-[var(--bs-primary)]"
								:aria-label="showPassword ? 'Ocultar contraseña' : 'Mostrar contraseña'">
								<i :class="showPassword ? 'fa fa-eye-slash' : 'fa fa-eye'"></i>
							</button>
						</div>

						<!-- @error('password') -->
					</div>

					<!-- Botón principal -->
					<div>
						<button type="submit" :disabled="loading"
							class="w-full text-white font-semibold py-2.5 rounded-lg transition-all duration-200 flex items-center justify-center"
							:style="{ backgroundColor: getPrimaryColor(), borderColor: getPrimaryColor() }">
							<i class="fa fa-sign-in-alt mr-2"></i>
							{{ loading ? 'Ingresando...' : 'Iniciar Sesión' }}
						</button>
					</div>

					<!-- ERROR -->
					<div v-if="error" class="text-red-600 text-sm text-center">
						{{ error }}
					</div>

					<!-- LINKS -->
					<div class="text-center text-sm mt-4 space-y-1">
						<a href="#" class="text-[var(--bs-primary)] hover:underline">
							¿Olvidó su Contraseña?
						</a>
						<div>
							<a href="https://onway.entelocean.com/Home/Index/es" target="_blank"
								class="text-gray-600 hover:text-[var(--bs-primary)]">
								Ir a Onway <i class="fa fa-external-link-alt ml-1"></i>
							</a>
						</div>
					</div>
				</form>
			</div>
		</div>

		<!-- Columna derecha -->
		<div class="hidden md:flex md:w-2/3 overflow-hidden items-center justify-center">
			<img :src="getBackground()" alt="Imagen decorativa"
				class="w-full h-[90vh] object-cover object-center -translate-y-[15%]" />
		</div>
	</div>
</template>

<script setup>
	import { ref, computed } from 'vue'
	import { useRouter } from 'vue-router'
	import { useAuthStore } from '../stores/auth'

	const email = ref('')
	const password = ref('')
	const showPassword = ref(false)

	const router = useRouter()
	const auth = useAuthStore()

	const loading = computed(() => auth.loading)
	const error = computed(() => auth.error)

	function togglePassword() {
		showPassword.value = !showPassword.value
	}

	function getEmblema() {
		const url = getComputedStyle(document.documentElement)
			.getPropertyValue('--emblema-design')
			.trim()
		if (url.startsWith('url(')) return url.slice(4, -1).replace(/["']/g, '')
		return '/config/default-emblema.png'
	}

	function getPrimaryColor() {
		return (
			getComputedStyle(document.documentElement)
				.getPropertyValue('--bs-primary')
				.trim() || '#004aad'
		)
	}

	async function submitLogin() {
		auth.error = null
		try {
			await auth.login(email.value, password.value)
			await router.push('/dashboard')
		} catch (err) {
			console.error('Error en login:', err)
			auth.error = 'Credenciales inválidas o error de conexión.'
		}
	}

	function getBackground() {
		const url = getComputedStyle(document.documentElement)
			.getPropertyValue('--background-design')
			.trim()
		if (url.startsWith('url(')) return url.slice(4, -1).replace(/["']/g, '')
		return '/config/default_fondo.png'
	}
</script>

<style scoped>
	html,
	body {
		overflow: hidden;
		font-family: 'Roboto', sans-serif;
	}

	button:disabled {
		opacity: 0.6;
		cursor: not-allowed;
	}
</style>