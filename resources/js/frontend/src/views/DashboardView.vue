<template>
    <div class="dashboard">
    <h1>Bienvenido, {{ auth.user?.email }}</h1>
    <p>Este es el Dashboard (valores en duro)</p>
    <button @click="logout">Cerrar sesión</button>
    </div>
</template>

<script setup>
import { useAuthStore } from '../stores/auth'
const auth = useAuthStore()

const logout = () => {
    auth.logout()
}
</script>

<style scoped>
.dashboard {
    text-align: center;
    margin-top: 100px;
}
button {
    padding: 0.7rem 1.5rem;
    background-color: #ef4444;
    color: white;
    border: none;
    border-radius: 8px;
    cursor: pointer;
}
</style>