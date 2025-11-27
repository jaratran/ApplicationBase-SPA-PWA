<!-- resources/js/frontend/src/views/DashboardView.vue -->

<template>
	<div class="container">
		<!-- 🧭 Título principal -->
		<div class="card mb-4">
			<div class="card-header bg-primary text-white fs-5">
				Panel de Control
			</div>

			<!-- Consolidado semanal -->
			<div class="card-body">
				<div class="card mb-4">
					<div
						class="card-header bg-secondary text-white fs-5 d-flex justify-content-between align-items-center flex-wrap">
						Consolidado Semanal, desde el <strong>{{ panel.desdeFecha }}</strong> al <strong>{{
							panel.hastaFecha
							}}</strong>
					</div>

					<div class="card-body">
						<!-- 🔹 ALERTAS AL ESTILO EcoRuta -->
						<AlertSystem />

						<div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-stretch">
							<!-- Gráfico Tons Plan x Sucursal y el % del Total - Hoy -->
							<div class="col-span-12 md:col-span-4">
								<div class="card h-full">
									<div class="card-header bg-primary text-white py-2">
										Toneladas Plan x Sucursal y % del Total - Hoy
									</div>
									<div class="card-body p-3">
										<div class="placeholder-chart">Gráfico pendiente</div>
									</div>
								</div>
							</div>

							<!-- KPI de Toneladas (1) -->
							<div class="col-span-12 md:col-span-2 py-2">
								<div class="card bg-secondary text-white text-center">
									<div class="card-header py-2 border-b border-black/40">Tons a Recibir ETA Hoy</div>
									<div class="card-body py-3">
										<h2>{{ formatNumber(panel.kpiRcvrHoy) }}</h2>
									</div>
								</div>
							</div>

							<!-- Gráfico Tons Plan x día vs Tons Real x día - Últimos 7 días -->
							<div class="col-span-12 md:col-span-4">
								<div class="card h-full">
									<div class="card-header bg-primary text-white py-2">
										Tons Plan x día vs Tons Real x día - Últimos 7 días
									</div>
									<div class="card-body p-3">
										<div class="placeholder-chart">Gráfico pendiente</div>
									</div>
								</div>
							</div>

							<!-- KPIs de Toneladas (2) -->
							<div class="col-span-12 md:col-span-2">
								<div class="card bg-success text-white text-center mt-2 mb-3">
									<div class="card-header py-2 border-b border-black/40">Acum Plan Ults 7 días</div>
									<div class="card-body py-3">
										<h2>{{ formatNumber(panel.kpiAcumPlan) }}</h2>
									</div>
								</div>

								<div class="card bg-warning text-white text-center">
									<div class="card-header py-2 border-b border-black/40">Acum Real Ults 7 días</div>
									<div class="card-body py-3">
										<h2>{{ formatNumber(panel.kpiAcumReal) }}</h2>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>

				<!-- Programa Diario -->
				<div class="card">
					<div
						class="card-header bg-secondary text-white fs-5 d-flex justify-content-between align-items-center flex-wrap">
						<div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-stretch">
							<div class="col-span-12 md:col-span-8">
								Programa Diario vigente el día de HOY : <strong>{{ panel.fecha_vigente_programa
									}}</strong><br />
								<span v-if="panel.version_programa_diario">
									<span class="italic">Versión {{ panel.version_programa_diario }}</span>
								</span>
							</div>
							<div class="col-span-12 md:col-span-4 text-end">
								<strong>Total kilos considerados:</strong><br />
								<span class="text-normal">{{ formatNumber(panel.totalKilosEstimados) }} kg</span>
							</div>
						</div>
					</div>

					<div class="card-body">
						<div class="table-responsive">
							<table
								class="table table-striped table-bordered table-hover align-middle text-center shadow-sm">
								<thead>
									<tr>
										<th>Estado</th>
										<th>Novedad</th>
										<th>Sucursal</th>
										<th>Procedencia</th>
										<th>Proveedor</th>
										<th>Fecha y hora Retiro</th>
										<th>Camión</th>
										<th>TK/BIN</th>
										<th>Hora</th>
										<th>ETA</th>
										<th>Kg. Est.</th>
										<th>Producto</th>
										<th>Especie</th>
										<th>Carga Bins</th>
									</tr>
								</thead>
								<tbody>
									<tr v-for="(detalle, index) in panel.detalles" :key="index">
										<td>{{ detalle.estado }}</td>
										<td>{{ detalle.novedad }}</td>
										<td>{{ detalle.sucursal }}</td>
										<td>{{ detalle.procedencia }}</td>
										<td>{{ detalle.proveedor }}</td>
										<td>{{ detalle.fecha_hora_retiro }}</td>
										<td>{{ detalle.camion }}</td>
										<td>{{ detalle.tipo_retiro }}</td>
										<td>{{ detalle.duracion_viaje }}</td>
										<td>{{ detalle.eta }}</td>
										<td>{{ formatNumber(detalle.kg_estimados) }}</td>
										<td>{{ detalle.producto }}</td>
										<td>{{ detalle.especie }}</td>
										<td>{{ detalle.bins ?? '-' }}</td>
									</tr>
									<tr v-if="panel.detalles.length === 0">
										<td colspan="14" class="text-center text-muted py-4">
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
import { ref, onMounted } from "vue";
import { useAlertStore } from '../stores/alert'

import axios from "axios";

const alert = useAlertStore()

const panel = ref({
					fecha_vigente_programa: "",
					version_programa_diario: null,
					totalKilosEstimados: 0,
					desdeFecha: "12-07-1972",
					hastaFecha: "18-02-1975",
					kpiRcvrHoy: 0,
					kpiAcumPlan: 0,
					kpiAcumReal: 0,
					detalles: [],
				});

onMounted(() => {
	alert.prepare()														// Mostrar o limpiar alert si (pendiente/persistente)

	fetchPanelData()
})

async function fetchPanelData() {
	try {
		const response = await axios.get("/api/panel/datos");

		if (response.data.status === "success") {
			panel.value = response.data.data;
		} else {
			console.warn("Respuesta no exitosa:", response.data.message);
		}

	} catch (error) {
		console.error("Error al obtener datos del panel:", error);
	}
}

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
