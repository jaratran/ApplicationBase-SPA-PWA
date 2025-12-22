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
					Recuperar Contraseña
				</div>

				<div class="card-login-body">
					<!-- 🔹 ALERTAS AL ESTILO EcoRuta -->
					<AlertSystem />

					<form @submit.prevent="submitEmail">
						<!-- EMAIL -->
						<div class="mb-4">
							<label class="form-label" for="email">Correo Electrónico</label>
							<input class="form-control" id="email" v-model="email" type="email" required autofocus
								placeholder="Correo Electrónico" autocomplete="email">
						</div>

						<!-- BOTONERA AL ESTILO EcoRuta -->
						<div class="grid grid-cols-12 gap-3 mt-4">

							<!-- Botón principal (col-8) -->
							<div class="col-span-8">
								<button type="submit" :disabled="loading" class="btn btn-primary w-full">
									<i class="fa fa-edit"></i>
									{{ loading ? 'Enviando...' : 'Envíe Correo de Recuperación' }}
								</button>
							</div>

							<!-- Botón cancelar (col-4) -->
							<div class="col-span-4">
								<button type="button" class="btn btn-secondary w-full" @click="router.push('/login')">
									<i class="fa fa-times"></i>
									Cancelar
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

	</div>
</template>

<script setup>
	import { ref, computed, onMounted, onUnmounted } from 'vue'
	import { useAlertStore } from '../../stores/alert'
	import { useRouter } from 'vue-router'
	import api from '../../services/api'						// tu instancia Axios API
	import '../../../css/auth.css'						// CSS Globales para vistas de Login, Cambio y Recuperación de Contraseña

	const router = useRouter()
	const alert = useAlertStore()

	const email = ref('')

	const emblemaSrc = ref(getEmblema());
	const backgroundSrc = ref(getBackground());

	const loading = ref(false)

	onMounted(() => {
		alert.prepare()														// Mostrar o limpiar alert si (pendiente/persistente)

		// 🔁 Releer branding cuando los parámetros estén listos
		window.addEventListener('design-parameters-applied', onDesignParametersApplied);
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

	async function submitEmail() {
		loading.value = true

		try {
			const { data } = await api.post('/forgot-password', { email: email.value })
			alert.show(data.message, 'success', true) 													// Alerta persistente para vista en Login
			return router.push('/login')																// Con return evita que siga ejecutándose el resto del código

		} catch (error) {
			const msg = error.response?.data?.message || 'No fue posible enviar el enlace.'
			alert.show(msg, 'error')																	// Mostrar alerta local SI hubo error

		} finally {
			loading.value = false
		}
	}
</script>

<style scoped></style>
