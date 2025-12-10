<!doctype html>
<html lang="es">
    <head>
        <meta charset="utf-8" />

        <!--
            user-scalable=no: bloquea zoom manual en móviles para mantener layout fijo (intencional, estilo app).
            Si se requiere accesibilidad, eliminar esta restricción.
        -->
        <meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>Calidad PWA</title>

        <!-- Fuente Roboto, igual que EcoRuta -->
        <link href="https://fonts.googleapis.com/css2?family=Roboto&display=swap" rel="stylesheet">
        <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">

		<!-- Usar Font Awesome 6 (simple y sin integrity) -->
		<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet" referrerpolicy="no-referrer" />

		<!-- PWA: manifest - Chrome, Android, Safari y Windows detectan la PWA exclusivamente desde aca. -->
		<link rel="manifest" href="/manifest.json">
		<!-- PWA: color de tema - obligatorio para Android + iOS -->
		<meta name="theme-color" content="#0f766e">

		<!-- 🔹 Aquí cargamos los CSS con estilos de compatibilidad -->
		<!-- CSS + JS AUTOMÁTICOS POR VITE -->
		@vite([
			'resources/css/bootstrap-sim.css',
			'resources/css/app.css'
		])
    </head>

    <body class="antialiased">
        <div id="app"></div>

		{{-- 🔹 Aquí inyectas las constantes desde PHP hacia JS (window.constantes) --}}
		@include('includes.constantes-js')

		{{-- 🔹 Y después cargas tu bundle de Vue --}}
		@vite('resources/js/frontend/main.js')
	</body>
</html>
