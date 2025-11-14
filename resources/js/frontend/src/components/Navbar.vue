<!-- resources/js/frontend/src/components/Navbar.vue -->
<template>
    <nav
        class="navbar h-12 bg-white border-b shadow-sm flex items-center justify-between px-3 sticky top-0 z-40 select-none">
        <!-- IZQUIERDA: Toggle + Logo + Título -->
        <div class="flex items-center gap-3 relative">
            <!-- Toggle -->
            <button id="toggleSidebar" type="button"
                class="inline-flex items-center justify-center w-9 h-9 rounded border border-gray-300 bg-white hover:bg-gray-100"
                @click="$emit('toggle-sidebar')">
                <i class="fas fa-th text-gray-700 text-sm"></i>
            </button>

            <!-- LOGO igual que EcoRuta -->
            <a href="/dashboard" class="block">
                <img :src="logoUrl" alt="Logo" class="h-7 object-contain" />
            </a>
        </div>

        <!-- DERECHA: Avatar + Dropdown -->
        <div class="relative" ref="menuRef">
            <button @click="toggleMenu"
                class="w-9 h-9 rounded-full bg-gray-200 flex items-center justify-center hover:ring-2 hover:ring-gray-300">
                <i class="fas fa-user text-gray-600"></i>
            </button>

            <transition name="fade">
                <div v-if="menuOpen"
                    class="absolute right-0 mt-2 w-40 rounded-md shadow-lg bg-white border border-gray-200 py-1 z-50">
                    <button class="w-full text-left px-4 py-2 text-sm hover:bg-gray-100 flex items-center gap-2"
                        @click="goPerfil">
                        <i class="fas fa-user-circle text-gray-600"></i>
                        Mi Perfil
                    </button>

                    <button
                        class="w-full text-left px-4 py-2 text-sm hover:bg-gray-100 flex items-center gap-2 text-red-600"
                        @click="logout">
                        <i class="fas fa-sign-out-alt"></i>
                        Cerrar Sesión
                    </button>
                </div>
            </transition>
        </div>
    </nav>
</template>

<script setup>
    import { ref, onMounted, onBeforeUnmount, computed } from 'vue'
    import { useRouter } from 'vue-router'
    import { useAuthStore } from '../stores/auth'

    // Referencias
    const menuRef = ref(null)
    const menuOpen = ref(false)

    const router = useRouter()
    const auth = useAuthStore()

    const toggleMenu = () => {
        menuOpen.value = !menuOpen.value
    }

    const handleClickOutside = (event) => {
        if (menuRef.value && !menuRef.value.contains(event.target)) {
            menuOpen.value = false
        }
    }

    onMounted(() => {
        window.addEventListener('click', handleClickOutside)
    })

    onBeforeUnmount(() => {
        window.removeEventListener('click', handleClickOutside)
    })

    const goPerfil = () => {
        menuOpen.value = false
        router.push('/perfil')
    }

    const logout = async () => {
        menuOpen.value = false
        await auth.logout()
        router.push('/login')
    }

    const logoUrl = computed(() => {
        // applyDesignParameters() deja estos datos aquí
        const p = window.DesignParameters || {}

        if (p.logo_design) {
            return `/config/${p.logo_design}`
        }

        // Fallback si no existe todavía
        return '/images/default-logo.png'
    })
</script>

<style scoped>
    .navbar {
        border-bottom: none !important;
        box-shadow: none !important;
        background-color: #f8f9fa !important;
    }

    #toggleSidebar {
        border: none !important;
        background-color: transparent !important;
        box-shadow: none !important;
    }

    #toggleSidebar:focus,
    #toggleSidebar:active {
        outline: none !important;
        box-shadow: none !important;
    }

    #toggleSidebar i {
        font-size: 1.45rem !important;
    }

    .fade-enter-active,
    .fade-leave-active {
        transition: opacity 0.15s ease;
    }

    .fade-enter-from,
    .fade-leave-to {
        opacity: 0;
    }
</style>
