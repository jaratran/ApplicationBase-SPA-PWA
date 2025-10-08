<template>
    <div class="login-container">
    <h1>Ingreso al Sistema</h1>

    <form @submit.prevent="submitLogin">
        <div>
        <label>Email</label>
        <input v-model="email" type="email" required />
        </div>

        <div>
        <label>Contraseña</label>
        <input v-model="password" type="password" required />
        </div>

        <button type="submit" :disabled="loading">
        {{ loading ? 'Ingresando...' : 'Entrar' }}
        </button>
    </form>

    <p v-if="error" class="error">{{ error }}</p>
    </div>
</template>

<script setup>
import { ref } from 'vue'
import { useAuthStore } from '../stores/auth'

const email = ref('')
const password = ref('')
const auth = useAuthStore()

const submitLogin = async () => {
    await auth.login(email.value, password.value)
}

const loading = computed(() => auth.loading)
const error = computed(() => auth.error)
</script>

<style scoped>
.login-container {
    max-width: 400px;
    margin: 80px auto;
    padding: 2rem;
    border: 1px solid #ddd;
    border-radius: 12px;
    text-align: center;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
}
label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 600;
}
input {
    width: 100%;
    padding: 0.5rem;
    margin-bottom: 1rem;
    border-radius: 8px;
    border: 1px solid #ccc;
}
button {
    width: 100%;
    padding: 0.7rem;
    background-color: #3b82f6;
    color: white;
    font-weight: bold;
    border: none;
    border-radius: 8px;
    cursor: pointer;
}
.error {
    color: red;
    margin-top: 1rem;
}
</style>