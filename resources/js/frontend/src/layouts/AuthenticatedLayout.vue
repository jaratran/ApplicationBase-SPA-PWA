<!-- resources/js/frontend/src/layouts/AuthenticatedLayout.vue -->
<template>
    <div class="min-h-screen bg-white flex flex-col">
        <!-- NAVBAR -->
        <Navbar @toggle-sidebar="toggleSidebar" />

        <!-- SIDEMENU flotante -->
        <SideMenu :open="isSidebarOpen" />

        <!-- OVERLAY (escritorio y móvil) -->
        <div v-if="isSidebarOpen" class="fixed inset-0 bg-black/40 z-40" @click="closeSidebar">
        </div>

        <!-- Contenido siempre centrado -->
        <main class="p-4 z-0">
            <slot />
        </main>
    </div>
</template>

<script setup>
import { ref } from 'vue'
import Navbar from '../components/Navbar.vue'
import SideMenu from '../components/SideMenu.vue'

const isSidebarOpen = ref(false)

const toggleSidebar = () => isSidebarOpen.value = !isSidebarOpen.value
const closeSidebar = () => isSidebarOpen.value = false
</script>
