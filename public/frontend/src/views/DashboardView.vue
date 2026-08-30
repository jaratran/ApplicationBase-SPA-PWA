<template>
	<div class="container">
		<!-- 🧭 Título principal -->
		<div class="card mb-4">
			<div class="card-header bg-primary text-white fs-5">
				Panel de Control
			</div>

			<!-- Resumen del período -->
			<div class="card-body">
				<div class="card mb-4">
					<div
						class="card-header bg-secondary text-white fs-5 d-flex justify-content-between align-items-center flex-wrap">
						Resumen del período, desde el <strong>{{ panelStore.data?.periodo?.desde }}</strong> al <strong>{{
							panelStore.data?.periodo?.hasta
							}}</strong>
					</div>

					<div class="card-body">
						<!-- 🔹 ALERTAS AL ESTILO EcoRuta -->
						<AlertSystem />

						<div v-if="panelStore.loading" class="text-center text-muted py-4">
							<i class="fas fa-spinner fa-spin"></i>
							<p>Cargando panel…</p>
						</div>
						<div v-else>
							<div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-stretch">
								<!-- Resumen -->
								<div class="col-span-12 md:col-span-4">
									<div class="card h-full">
										<div class="card-header bg-primary text-white py-2">
											Resumen
										</div>
										<div class="card-body p-3">
											<div class="placeholder-chart">Gráfico pendiente</div>
										</div>
									</div>
								</div>

								<!-- Indicador principal -->
								<div class="col-span-12 md:col-span-2 py-2">
									<div class="card bg-secondary text-white text-center">
										<div class="card-header py-2 border-b border-black/40">Indicador principal
										</div>
										<div class="card-body py-3">
											<h2>{{ formatNumber(panelStore.data?.kpiPrincipal) }}</h2>
										</div>
									</div>
								</div>

								<!-- Tendencia -->
								<div class="col-span-12 md:col-span-4">
									<div class="card h-full">
										<div class="card-header bg-primary text-white py-2">
											Tendencia
										</div>
										<div class="card-body p-3">
											<div class="placeholder-chart">Gráfico pendiente</div>
										</div>
									</div>
								</div>

								<!-- Indicadores secundarios -->
								<div class="col-span-12 md:col-span-2">
									<div class="card bg-success text-white text-center mt-2 mb-3">
										<div class="card-header py-2 border-b border-black/40">Indicador secundario
										</div>
										<div class="card-body py-3">
											<h2>{{ formatNumber(panelStore.data?.kpiSecundario) }}</h2>
										</div>
									</div>

									<div class="card bg-warning text-white text-center">
										<div class="card-header py-2 border-b border-black/40">Indicador terciario
										</div>
										<div class="card-body py-3">
											<h2>{{ formatNumber(panelStore.data?.kpiTerciario) }}</h2>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>

				<!-- Detalle -->
				<div class="card">
					<div
						class="card-header bg-secondary text-white fs-5 d-flex justify-content-between align-items-center flex-wrap">
						Detalle
					</div>

					<div class="card-body">
						<div class="table-responsive">
							<table
								class="table table-striped table-bordered table-hover align-middle text-center shadow-sm">
								<thead>
									<tr>
									<th>Referencia</th>
									<th>Descripción</th>
									<th>Valor</th>
									</tr>
								</thead>
								<tbody>
								<tr v-for="(detalle, index) in panelStore.data?.detalles ?? []" :key="index">
									<td>{{ detalle.referencia }}</td>
									<td>{{ detalle.descripcion }}</td>
									<td>{{ detalle.valor }}</td>
								</tr>
								<tr v-if="(panelStore.data?.detalles ?? []).length === 0">
									<td colspan="3" class="text-center text-muted py-4">
											<em>No hay datos disponibles</em>
										</td>
									</tr>
								</tbody>
							</table>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</template>

<script setup>
	import { onMounted } from "vue";
	import { useAlertStore } from '@/stores/alert'
	import { usePanelStore } from '@/stores/panel'

	const alert = useAlertStore()
	const panelStore = usePanelStore()

	onMounted(async () => {
		alert.prepare()														// Mostrar o limpiar alert si (pendiente/persistente)

		await panelStore.fetchPanel()
	})

	function formatNumber(value) {
		if (value === null || value === undefined) return "-";
		return new Intl.NumberFormat("es-CL").format(value);
	}
</script>

<style scoped>
	/* === Headers de cards secundarias propias de DashBoard (Gráficos y KPIs) === */
	.card-header:not(.fs-5) {
		font-size: 0.9rem;  /* ~14px, sobrio */
		font-weight: 400;  /* liviano, contrasta con el h2 grande */
		letter-spacing: 0.3px;
		line-height: 1.3;
	}

	/* === Body de cards secundarias de KPIs === */
	.card-body h2 {
		font-size: 1.8rem;
		font-weight: 400 !important;
		margin: 0;
	}

	/* === Placeholder de gráficos === */
	.placeholder-chart {
		height: 220px;
		display: flex;
		align-items: center;
		justify-content: center;
		background: #f8f9fa;
		border: 1px dashed #ccc;
		color: #999;
		font-style: italic;
	}
</style>
