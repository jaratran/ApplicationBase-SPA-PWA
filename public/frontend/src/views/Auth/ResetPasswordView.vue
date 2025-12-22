<template>
	<div class="flex flex-row flex-wrap min-h-screen items-center">
		<!-- Columna izquierda -->
		<div class="md:w-1/3 p-3 w-full text-center">
			<div class="card-login shadow-2xl rounded-lg p-6">
				<!-- Emblema del Sitio -->
				<div class="mb-4">
					<img :src="emblemaSrc" alt="Emblema del Sitio"
						class="w-full h-auto max-h-[180px] object-contain object-center" />
				</div>

				<!-- Título principal -->
				<div class="card-login-header bg-transparent border-0 text-xl" :style="{ color: getPrimaryColor() }">
					Restablecer Contraseña
				</div>

				<div class="globoPasswordMoviles">
					<div class="globoInternoPasswordMoviles">

						<h6 class="tituloGloboPasswordMoviles">La contraseña debe ...</h6>

						<div class="textoGloboPasswordMoviles">
							<p>Tener mínimo ocho caracteres.</p>
							<p>Contener al menos un número.</p>
							<p>Contener al menos una mayúscula y una minúscula.</p>
						</div>
					</div>
				</div>

				<div class="card-login-body">
					<!-- 🔹 ALERTAS AL ESTILO EcoRuta -->
					<AlertSystem />

					<form @submit.prevent="submitResetPassword">
						<!-- PASSWORD 1 + OJO -->
						<div class="mb-4">
							<label class="form-label" for="password">Ingrese su nueva Contraseña</label>

							<div class="relative">
								<input class="input-password" v-model="passwordNew" id="passwordNew" required
									placeholder="Nueva Contraseña" :type="showPasswordNew ? 'text' : 'password'"
									autocomplete="new-password" />

								<button class="boton-ojo" type="button" @click="togglePasswordNew"
									:aria-label="showPasswordNew ? 'Ocultar contraseña' : 'Mostrar contraseña'">
									<i :class="showPasswordNew ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye'"></i>
								</button>
							</div>
						</div>

						<!-- PASSWORD 2 + OJO -->
						<div class="mb-4">
							<label class="form-label" for="password">Confirme su nueva Contraseña</label>

							<div class="relative">
								<input class="input-password" v-model="passwordConf" id="passwordConf" required
									placeholder="Confirme Nueva Contraseña"
									:type="showPasswordConf ? 'text' : 'password'" autocomplete="conf-password" />

								<button class="boton-ojo" type="button" @click="togglePasswordConf"
									:aria-label="showPasswordConf ? 'Ocultar contraseña' : 'Mostrar contraseña'">
									<i :class="showPasswordConf ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye'"></i>
								</button>
							</div>
						</div>

						<!-- BOTONERA AL ESTILO EcoRuta -->
						<div class="grid grid-cols-12 gap-3 mt-4">

							<!-- Botón principal (col-12) -->
							<div class="col-span-12">
								<button type="submit" :disabled="loading" class="btn btn-primary w-full">
									<i class="fa fa-edit"></i>
									{{ loading ? 'Restableciendo...' : 'Restablezca Contraseña' }}
								</button>
							</div>

						</div>
					</form>
				</div>
			</div>
		</div>

		<!-- Columna derecha -->
		<div class="md:w-2/3 pl-5 hidden md:block overflow-hidden">
			<img class="login-image" :src="backgroundSrc" alt="Imagen decorativa" />
		</div>

		<!-- “globo” con condiciones de contraseña -->
		<div class="hidden md:block absolute globoPassword">
			<div class="bg-white rounded shadow p-3">
				<h6 class="h6 tituloGloboPassword" :style="{ color: getPrimaryColor() }">La contraseña debe cumplir con:
				</h6>

				<ul class="textoGloboPassword">
					<li>Tener mínimo ocho caracteres.</li>
					<li>Contener al menos un número.</li>
					<li>Contener al menos una mayúscula y una minúscula.</li>
				</ul>
			</div>
		</div>
	</div>
</template>

<script setup>
	import { ref, computed, onMounted, onUnmounted } from 'vue'
	import { useAlertStore } from '../../stores/alert'
	import { useRoute, useRouter } from 'vue-router'

	import api from '../../services/api'						// tu instancia Axios API

	import '../../../css/auth.css'						// CSS Globales para vistas de Login, Cambio y Recuperación de Contraseña

	const route = useRoute()									// ← aquí vienen token y email
	const router = useRouter()									// ← navegar después del éxito
	const alert = useAlertStore()

	const passwordNew = ref('')
	const showPasswordNew = ref(false)
	const passwordConf = ref('')
	const showPasswordConf = ref(false)

	const loading = ref(false)

	const emblemaSrc = ref(getEmblema());
	const backgroundSrc = ref(getBackground());

	onMounted(() => {
		alert.prepare()														// Mostrar o limpiar alert si (pendiente/persistente)

		// 🔁 Releer branding cuando los parámetros estén listos
		window.addEventListener( 'design-parameters-applied', onDesignParametersApplied );
	})
	onUnmounted(() => {
		window.removeEventListener('design-parameters-applied', onDesignParametersApplied);
	});
	function onDesignParametersApplied() {
		emblemaSrc.value = getEmblema();
		backgroundSrc.value = getBackground();
	}

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

	function togglePasswordNew() {
		showPasswordNew.value = !showPasswordNew.value
	}
	function togglePasswordConf() {
		showPasswordConf.value = !showPasswordConf.value
	}

	const validarFormulario = () => {
		const pass = passwordNew.value
		const conf = passwordConf.value

		// Reglas de validación
		const reglas = {
			minimo: pass.length >= 8,
			numero: /[0-9]/.test(pass),
			mayusMinus: /[A-Z]/.test(pass) && /[a-z]/.test(pass),
			iguales: pass === conf
		}

		// Errores
		if (!pass || !conf) {
			alert.show("Debe ingresar y confirmar la nueva contraseña.", 'error')
			return false
		}

		if (!reglas.minimo) {
			alert.show("La contraseña debe tener al menos 8 caracteres.", 'error')
			return false
		}

		if (!reglas.numero) {
			alert.show("La contraseña debe contener al menos un número.", 'error')
			return false
		}

		if (!reglas.mayusMinus) {
			alert.show("Debe incluir mayúsculas y minúsculas.", 'error')
			return false
		}

		if (!reglas.iguales) {
			alert.show("Las contraseñas no coinciden.", 'error')
			return false
		}

		return true
	}

	const submitResetPassword = async () => {
		loading.value = true
		alert.prepare()

		const token = route.params.token
		const email = route.query.email

		// 1. Validación del enlace (salida temprana)
		if (!token || !email) {
			alert.show("El enlace de restablecimiento no es válido.", 'error', true)
			loading.value = false
			return router.push('/login')
		}

		// 2. Validación del formulario (salida temprana)
		if (!validarFormulario()) {
			loading.value = false
			return
		}

		try {
			// 3. Construcción del payload
			const payload = {
				token,
				email,
				password: passwordNew.value,
				password_confirmation: passwordConf.value
			}

			// 4. Llamada API
			await api.post('/reset-password', payload)

			// 5. Éxito: alerta persistente + navegación
			alert.show( "Contraseña actualizada correctamente. Ahora puede iniciar sesión.", 'success', true )
			return router.push('/login')

		} catch (error) {
			const msg = error.response?.data?.message || 'No se pudo actualizar la contraseña. Intente nuevamente.'
			console.error(msg)
			alert.show(msg, 'error', true)																	// Mostrar alerta local SI hubo error
			return router.push('/login')

		} finally {
			// 6. SIEMPRE se ejecuta (excepto return temprano)
			loading.value = false
		}
	}
</script>

<style scoped></style>
