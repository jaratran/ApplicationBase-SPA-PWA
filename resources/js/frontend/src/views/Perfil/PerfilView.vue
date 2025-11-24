<template>
	<div class="container">
		<div v-if="auth.perfil">
			<div class="row">
				<!-- Columna Izquierda -->
				<div class="col-md-4">
					<div class="card shadow-sm">
						<div class="card-header bg-primary text-white fs-5">
							Mi Perfil
						</div>

						<div class="card-body text-center">
							<img :src="avatarMedium" class="rounded-circle avatar-img" alt="Avatar">

							<h4 class="mt-3">
								{{ auth.perfil.nombre_usuario }} {{ auth.perfil.apellidos_usuario }}
							</h4>

							<hr class="my-3">

							<!-- Subir Avatar -->
							<form @submit.prevent="submitAvatar">
								<div class="form-group text-start px-2">
									<label for="avatarFile" class="form-label">Subir Foto de Perfil</label>

									<!-- Input visible tal como EcoRuta -->
									<input type="file" id="avatarFile" ref="fileInput" accept="image/*"
										class="form-control" />
								</div>

								<div class="text-center">
									<button type="submit" class="btn btn-primary btn-full">
										<i class="fa fa-refresh"></i> Actualizar Foto de Perfil
									</button>
								</div>
							</form>
						</div>
					</div>
				</div>

				<!-- Columna Derecha -->
				<div class="col-md-8">
					<div class="card shadow-sm">
						<div class="card-header bg-primary text-white fs-5">
							Mis Datos Personales
						</div>

						<div class="card-body">

							<!-- Botones -->
							<div class="flex gap-2 mb-3">
								<button @click="goPassword" class="btn btn-secondary">
									<i class="fa fa-key me-1"></i>
									Actualizar Contraseña
								</button>

								<button @click="goEditar" class="btn btn-primary">
									<i class="fa fa-edit me-1"></i>
									Modificar Perfil
								</button>
							</div>

							<!-- Datos Personales -->
							<h5 class="section-title">Datos Personales</h5>

							<table class="table table-bordered table-striped table-sm">
								<tbody>
									<tr>
										<th>Nombre</th>
										<td>{{ auth.perfil.nombre_usuario }}</td>
									</tr>
									<tr>
										<th>Apellidos</th>
										<td>{{ auth.perfil.apellidos_usuario }}</td>
									</tr>
									<tr>
										<th>RUT</th>
										<td>{{ auth.perfil.rut_usuario }}</td>
									</tr>
									<tr>
										<th>Email</th>
										<td>{{ auth.perfil.email }}</td>
									</tr>
									<tr>
										<th>Teléfono</th>
										<td>{{ auth.perfil.telefono }}</td>
									</tr>
									<tr>
										<th>Rol</th>
										<td>{{ auth.perfil.rol?.nombre }}</td>
									</tr>
									<tr>
										<th>Empresa/Sucursal</th>
										<td>{{ empresaSucursal }}</td>
									</tr>
								</tbody>
							</table>

							<!-- Dirección -->
							<h5 class="section-title mt-4">Dirección</h5>

							<table class="table table-bordered table-striped table-sm">
								<tbody>
									<tr>
										<th>Dirección</th>
										<td>{{ auth.perfil.direccion }}</td>
									</tr>
									<tr>
										<th>Comuna</th>
										<td>{{ auth.perfil.comuna?.nombre }}</td>
									</tr>
									<tr>
										<th>Región</th>
										<td>{{ auth.perfil.comuna?.region?.nombre }}</td>
									</tr>
								</tbody>
							</table>

						</div>
					</div>
				</div>
			</div>
		</div>

		<div v-else class="text-center text-muted py-5">
			<i class="fas fa-spinner fa-spin fa-2x"></i>
			<p class="mt-2">Cargando perfil...</p>
		</div>
	</div>
</template>

<script setup>
    import { ref, onMounted, computed } from 'vue'
    import api from '../../services/api'
    import { useRouter } from 'vue-router'
    import { useAuthStore } from '../../stores/auth'

    const auth = useAuthStore()
    const router = useRouter()

	onMounted(async () => {
		await auth.fetchPerfil()
	})

	const avatarMedium = computed(() => {
		if (!auth.perfil) return '/uploads/avatar/default_medium.jpg'
		return `/uploads/avatar/${auth.perfil.avatar}_medium.jpg`
	})

	const empresaSucursal = computed(() => {
		if (!auth.perfil) return ''
		const u = auth.perfil

        if (u.rol_id === 4) return u.sucursal?.nombre_sucursal ?? '-'
        if (u.rol_id === 5) return u.empresa?.razon_social ?? '-'

        return 'NA'
    })

    // Avatar upload
    const fileInput = ref(null)

const submitAvatar = async () => {
	console.log('fileInput:', fileInput.value)
	console.log('files:', fileInput.value?.files)

	const file = fileInput.value?.files?.[0]
	console.log('file:', file)

	if (!file) {
		alert('No se seleccionó archivo')
		return
	}

	const form = new FormData()
	form.append('avatar', file)

	await api.post('/perfil/avatar', form)
	await auth.fetchPerfil()
	await auth.fetchUser()   // ← recarga el usuario global
}

    // Navegación
    const goEditar = () => router.push('/perfil/editar')
    const goPassword = () => router.push('/perfil/password')
</script>

<style scoped>
    /* COLUMNA AVATAR */
    .avatar-img {
        max-width: 250px;
        height: auto;
        display: block;
        margin: auto;
        image-rendering: auto;  /* Para evitar pixelación en algunos navegadores */
    }
    .rounded-circle {
        border-radius: 50% !important;  /* Asegura avatar en círculo perfecto */
    }

    /* FORMULARIO ACTUALIZAR AVATAR */
    .form-group {
        margin-bottom: 1rem;
    }
    .form-label {
        font-size: .85rem;
        margin-bottom: .25rem;
    }
    /* === Input file IDÉNTICO al de Bootstrap 5 === */
    .form-control[type="file"] {
        background-color: #ffffff;    /* ← Área del texto: blanco */
        border: 1px solid #ced4da;
        border-radius: 0.25rem;
        width: 100%;
    }
    /* Botón del input file (Chrome, Edge, etc.) */
    .form-control[type="file"]::-webkit-file-upload-button {
        background-color: #f8f9fa;    /* ← Botón gris */
        border: 1px solid #ced4da;
        padding: 0.375rem 0.75rem;
        margin-right: .75rem;
        cursor: pointer;
    }
    /* Hover del botón */
    .form-control[type="file"]::-webkit-file-upload-button:hover {
        background-color: #e9ecef;    /* Gris más oscuro en hover */
    }

    /* Estilo de Cabecera Tabla Perfil LaPortada */
    .table th {
        width: 35%;
        background: #f0f0f0;
    }
</style>
