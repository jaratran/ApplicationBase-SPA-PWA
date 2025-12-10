<!-- resources/js/frontend/src/views/LoginView.vue -->

<template>
	<div class="flex flex-row flex-wrap min-h-screen items-center">
		<!-- Columna izquierda -->
		<div class="md:w-1/3 p-3 w-full text-center">
			<div class="card-login shadow-2xl rounded-lg p-6">
				<!-- Emblema del Sitio -->
				<div class="mb-4">
					<img :src="getEmblema()" alt="Emblema del Sitio"
						class="w-full h-auto max-h-[180px] object-contain object-center" />
				</div>

				<!-- Título principal -->
				<div class="card-login-header bg-transparent border-0 text-xl" :style="{ color: getPrimaryColor() }">
					Iniciar Sesión
				</div>

				<div class="card-login-body">
					<!-- 🔹 ALERTAS AL ESTILO EcoRuta -->
					<AlertSystem />

					<form @submit.prevent="submitLogin">
						<!-- EMAIL -->
						<div class="mb-4">
							<label class="form-label" for="email">Correo Electrónico</label>
							<input class="form-control" id="email" v-model="email" type="email" required autofocus
								placeholder="Correo Electrónico" autocomplete="email">
						</div>

						<!-- PASSWORD + OJO -->
						<div class="mb-4">
							<label class="form-label" for="password">Contraseña</label>

							<div class="relative">
								<input class="input-password" v-model="password" id="password" required
									placeholder="Contraseña" :type="showPassword ? 'text' : 'password'"
									autocomplete="current-password" />

								<button class="boton-ojo" type="button" @click="togglePassword"
									:aria-label="showPassword ? 'Ocultar contraseña' : 'Mostrar contraseña'">
									<i :class="showPassword ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye'"></i>
								</button>
							</div>
						</div>

						<!-- Botón principal -->
						<div class="mb-5">
							<button type="submit" :disabled="loading" class="btn btn-primary btn-full">
								<i class="fa fa-sign-in-alt"></i>
								{{ loading ? 'Ingresando...' : 'Inicie Sesión' }}
							</button>
						</div>

						<!-- LINKS -->
						<div class="flex flex-col space-y-4">
							<a class="btn btn-link" @click.prevent="router.push('/forgot-password')">
								¿Olvidó su Contraseña?
							</a>

							<a class="link-onway" target="_blank" href="https://onway.entelocean.com/Home/Index/es">
								Ir a Onway
								<i class="fa fa-external-link-alt icon-onway"></i>
							</a>
						</div>
					</form>
				</div>
			</div>
		</div>

		<!-- Columna derecha -->
		<div class="md:w-2/3 pl-5 hidden md:block overflow-hidden">
			<img class="login-image" :src="getBackground()" alt="Imagen decorativa" />
		</div>
	</div>
</template>

<script setup>
	import { ref, computed, onMounted } from 'vue'
	import { useAlertStore } from '../stores/alert'
	import { useAuthStore } from '../stores/auth'
	import { useRouter } from 'vue-router'

	import '../../../../css/auth.css'										// CSS Globales para vistas de Login, Cambio y Recuperación de Contraseña

	const alert = useAlertStore()
	const auth = useAuthStore()
	const router = useRouter()
	const loading = computed(() => auth.loading)

	const email = ref('')
	const password = ref('')
	const showPassword = ref(false)

	onMounted(() => {
		alert.prepare()														// Mostrar o limpiar alert si (pendiente/persistente)
	})

	function getPrimaryColor() {
		return (
			getComputedStyle(document.documentElement)
			.getPropertyValue('--bs-primary')
			.trim() || '#004aad'
		)
	}

	function getEmblema() {
		const url = getComputedStyle(document.documentElement)
		.getPropertyValue('--emblema-design')
		.trim()
		if (url.startsWith('url(')) return url.slice(4, -1).replace(/["']/g, '')
		return '/config/default_emblema.png'
	}

	function getBackground() {
		const url = getComputedStyle(document.documentElement)
			.getPropertyValue('--background-design')
			.trim()
		if (url.startsWith('url(')) return url.slice(4, -1).replace(/["']/g, '')
		return '/config/default_fondo.png'
	}

	function togglePassword() {
		showPassword.value = !showPassword.value
	}

	async function submitLogin() {
		const ok = await auth.login(email.value, password.value)

		if (ok) {
			return router.push('/dashboard')			// Con return evita que siga ejecutándose el resto del código
		}

		// Mostrar alerta al estilo EcoRuta
		if (auth.error) {
			alert.show(auth.error, 'error')					// Mostrar alerta local SOLO SI hubo error
		}
	}
</script>

<style scoped></style>
