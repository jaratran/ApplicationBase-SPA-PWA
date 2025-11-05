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

        @vite([
            'resources/css/bootstrap-sim.css',
            'resources/css/app.css',
            'resources/js/frontend/main.js'
        ])
    </head>

    <body class="antialiased">
        <div id="app"></div>
    </body>
</html>
