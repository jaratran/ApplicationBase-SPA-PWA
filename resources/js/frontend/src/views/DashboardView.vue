<template>
  <div class="dashboard">
    <h1>Bienvenido, {{ auth.user?.email }}</h1>
    <p>Este es el Dashboard (valores en duro)</p>
    <button @click="logout" :disabled="loading">
      {{ loading ? 'Cerrando sesión...' : 'Cerrar sesión' }}
    </button>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useAuthStore } from '../stores/auth'

const auth = useAuthStore()
const loading = computed(() => auth.loading)

const logout = async () => {
  try {
    await auth.logout()
  } catch (err) {
    console.error('Error al cerrar sesión:', err)
  }
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
