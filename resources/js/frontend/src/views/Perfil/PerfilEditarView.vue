<!-- resources/js/frontend/src/views/Perfil/PerfilEditarView.vue -->

<template>
	<div class="container">
		<div class="card mb-4">
			<div class="card-header bg-primary text-white fs-5">
				Modificar Perfil
			</div>

			<div class="card-body">
				<!-- 🔹 ALERTAS AL ESTILO EcoRuta -->
				<AlertSystem />

				<h5>Datos Personales</h5>

				<div v-if="auth.perfil">
					<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
						<div class="flex flex-col">
							<label class="mb-1 text-sm font-normal">Rut</label>
							<input type="text" v-model="form.rut_usuario" placeholder="Ej: 12.345.678-9" required
								maxlength="12" autofocus
								class="border border-gray-300 rounded px-3 py-2 focus:border-gray-400 focus:ring-0" />
						</div>

						<div class="flex flex-col">
							<label class="mb-1 text-sm font-normal">Nombres</label>
							<input type="text" v-model="form.nombre_usuario"
								class="border border-gray-300 rounded px-3 py-2 focus:border-gray-400 focus:ring-0" />
						</div>

						<div class="flex flex-col">
							<label class="mb-1 text-sm font-normal">Apellidos</label>
							<input type="text" v-model="form.apellidos_usuario"
								class="border border-gray-300 rounded px-3 py-2 focus:border-gray-400 focus:ring-0" />
						</div>
					</div>

					<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
						<div class="flex flex-col">
							<label class="mb-1 text-sm font-normal">Correo Electrónico</label>
							<input type="text" v-model="form.email"
								class="border border-gray-300 rounded px-3 py-2 focus:border-gray-400 focus:ring-0" />
						</div>

						<div class="flex flex-col">
							<label class="mb-1 text-sm font-normal">Teléfono</label>
							<input type="text" v-model="form.telefono"
								class="border border-gray-300 rounded px-3 py-2 focus:border-gray-400 focus:ring-0" />
						</div>
					</div>

					<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
						<div class="flex flex-col">
							<label class="mb-1 text-sm font-normal">Rol de Usuario</label>

							<input type="text" :value="auth.perfil.rol?.nombre" disabled
								class="border border-gray-300 rounded px-3 py-2 bg-gray-100 text-gray-500 cursor-not-allowed opacity-80" />
						</div>

						<!-- Sucursal (ROL_SOLICITANTE_PLANTA) -->
						<div v-if="auth.perfil.rol_id === constantes.ROL_SOLICITANTE_PLANTA" class="flex flex-col">
							<label class="mb-1 text-sm font-normal">Sucursal</label>
							<input type="text" :value="auth.perfil.sucursal.nombre_sucursal" disabled
								class="border border-gray-300 rounded px-3 py-2 bg-gray-100 text-gray-500 cursor-not-allowed opacity-80" />
						</div>

						<!-- Empresa (ROL_SOLICITANTE_PRODUCTOR) -->
						<div v-if="auth.perfil.rol_id === constantes.ROL_SOLICITANTE_PRODUCTOR" class="flex flex-col">
							<label class="mb-1 text-sm font-normal">Empresa</label>
							<input type="text" :value="auth.perfil.empresa.razon_social" disabled
								class="border border-gray-300 rounded px-3 py-2 bg-gray-100 text-gray-500 cursor-not-allowed opacity-80" />
						</div>
					</div>

					<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
						<div class="flex flex-col">
							<label class="mb-1 text-sm font-normal">Región</label>

							<select v-model="form.region_id" @change="loadComunas(form.region_id)"
								class="border border-gray-300 rounded px-3 py-2 bg-white focus:border-gray-400 focus:ring-0"
								required>
								<option value="">Seleccione una región</option>
								<option v-for="r in regiones" :key="r.id" :value="r.id">
									{{ r.nombre }}
								</option>
							</select>
						</div>

						<div class="flex flex-col">
							<label class="mb-1 text-sm font-normal">Comuna</label>

							<select v-model="form.comuna_id"
								class="border border-gray-300 rounded px-3 py-2 bg-white focus:border-gray-400 focus:ring-0"
								required>
								<option value="">Seleccione una comuna</option>
								<option v-for="c in comunas" :key="c.id" :value="c.id">
									{{ c.nombre }}
								</option>
							</select>
						</div>

						<div class="flex flex-col">
							<label class="mb-1 text-sm font-normal">Dirección</label>
							<input type="text" v-model="form.direccion" required
								class="border border-gray-300 rounded px-3 py-2 focus:border-gray-400 focus:ring-0" />
						</div>
					</div>
				</div>

				<div v-else class="text-center text-muted py-5">
					<i class="fas fa-spinner fa-spin fa-2x"></i>
					<p class="mt-2">Cargando perfil...</p>
				</div>
			</div>

			<div class="card-footer">
				<button @click="submitForm" class="btn btn-primary">
					<i class="fa fa-edit"></i>
					Actualizar Perfil
				</button>

				<button @click="cancelar" class="btn btn-secondary">
					<i class="fa fa-times"></i>
					Cancelar
				</button>
			</div>
		</div>
	</div>
</template>

<script setup>
	import { ref, onMounted, computed, watch } from 'vue'
	import { useAlertStore } from '../../stores/alert'
	import { useAuthStore } from '../../stores/auth'
	import { useLocationStore } from '../../stores/location'
	import { useRouter } from 'vue-router'
	import api from '../../services/api'

	const alert = useAlertStore()
	const auth = useAuthStore()
	const location = useLocationStore()
	const router = useRouter()

	const constantes = window.constantes;

	// 🟢 Form local reactivo
	const form = ref({
		rut_usuario: '',
		nombre_usuario: '',
		apellidos_usuario: '',
		email: '',
		telefono: '',
		region_id: '',
		comuna_id: '',
		direccion: '',
	})

	// 🧩 Listas cargadas desde backend
	const regiones = ref([])
	const comunas = ref([])

	onMounted(async () => {
		alert.prepare()														// Mostrar o limpiar alert si (pendiente/persistente)

		// 1) Cargar perfil del usuario
		await auth.fetchPerfil()

		// Cargar regiones siempre al entrar
		await loadRegiones()

		// 2) Pre-cargar datos del form cuando el perfil esté disponible
		if (auth.perfil) {
			form.value.rut_usuario = auth.perfil.rut_usuario
			form.value.nombre_usuario = auth.perfil.nombre_usuario
			form.value.apellidos_usuario = auth.perfil.apellidos_usuario
			form.value.email = auth.perfil.email
			form.value.telefono = auth.perfil.telefono
			form.value.region_id = auth.perfil.comuna?.region_id
			form.value.comuna_id = auth.perfil.comuna_id
			form.value.direccion = auth.perfil.direccion
		}

		// 3) Si ya tengo región, cargo sus comunas
		if (form.value.region_id) {
			await loadComunas(form.value.region_id)
		}
	})

	// 🔵 Observa si el perfil llega después (por si tardara la carga)
	watch(
		() => auth.perfil,
		(nuevo) => {
			if (!nuevo) return

			form.value = {
				rut_usuario: nuevo.rut_usuario,
				nombre_usuario: nuevo.nombre_usuario,
				apellidos_usuario: nuevo.apellidos_usuario,
				email: nuevo.email,
				telefono: nuevo.telefono,
				region_id: nuevo.comuna?.region_id ?? '',
				comuna_id: nuevo.comuna_id ?? '',
				direccion: nuevo.direccion ?? '',
			}
		},
		{ immediate: true }
	)

	const empresaSucursal = computed(() => {
		if (!auth.perfil) return ''
		const u = auth.perfil

		if (u.rol_id === constantes.ROL_SOLICITANTE_PLANTA) return u.sucursal?.nombre_sucursal ?? '-'
		if (u.rol_id === constantes.ROL_SOLICITANTE_PRODUCTOR) return u.empresa?.razon_social ?? '-'

		return 'NA'
	})

	const loadRegiones = async () => {
		try {
			regiones.value = await location.fetchRegiones()
		} catch (e) {
			console.error('Error cargando regiones:', e)
		}
	}

	const loadComunas = async (regionId) => {
		if (!regionId) {
			comunas.value = []
			form.value.comuna_id = ''
			return
		}

		try {
			comunas.value = await location.fetchComunas(regionId)

			// Si la comuna actual no pertenece a la región seleccionada, se limpia
			if (!comunas.value.some(c => c.id === form.value.comuna_id)) {
				form.value.comuna_id = ''
			}
		} catch (e) {
			console.error('Error cargando comunas:', e)
		}
	}

	const submitForm = async () => {
		try {
			const payload = { ...form.value }
			await api.post('/perfil/update', payload)

			// Si quieres refrescar perfil global
			await auth.fetchPerfil()
			alert.show('El perfil ha sido actualizado correctamente', 'success', true) // Alerta persistente para vista Perfil

		} catch (error) {
			alert.show('Error al actualizar el perfil.', 'error')

		} finally {
			// Redirigir al perfil
			router.push('/perfil')
		}
	}

	const cancelar = () => {
		router.push('/perfil')
	}
</script>

<style scoped>
</style>
