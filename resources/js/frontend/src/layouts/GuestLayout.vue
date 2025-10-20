<!-- resources/js/frontend/src/layouts/GuestLayout.vue -->

<template>
  <!--
    Layout base para vistas públicas (Guest)
    No contiene estructura específica del Login.
    Solo provee un contenedor general, fondo y espacio para la vista hija.
  -->

  <!-- Layout base: pantalla completa, fondo neutro, sin centrado forzado -->
  <div class="min-h-screen w-screen bg-white flex flex-col">
    <router-view class="flex-1" />
  </div>
</template>

<script setup>
// No requiere lógica específica.
// Mantiene la neutralidad del layout.
</script>
