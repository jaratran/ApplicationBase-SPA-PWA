<template>
	<!--
		Layout base para vistas públicas (Guest)
		No contiene estructura específica del Login.
		Solo provee un contenedor general, fondo y espacio para la vista hija.
	-->

	<!-- Layout base: pantalla completa, fondo neutro, sin centrado forzado -->
	<div class="min-h-screen w-screen bg-white flex flex-col">
		<div class="flex-1">
			<slot />
		</div>
	</div>
</template>

<script setup>
	// No requiere lógica específica.
	// Mantiene la neutralidad del layout.
</script>
