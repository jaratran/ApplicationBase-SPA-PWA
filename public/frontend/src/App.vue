<!-- resources/js/frontend/src/App.vue -->
<template>
	<component :is="layoutComponent">
		<router-view />
	</component>
</template>

<script setup>
	import { computed } from 'vue'
	import { useRoute } from 'vue-router'

	import GuestLayout from './layouts/GuestLayout.vue'
	import AuthenticatedLayout from './layouts/AuthenticatedLayout.vue'

	const route = useRoute()

	const layoutComponent = computed(() => {
		return route.meta.requiresAuth
			? AuthenticatedLayout
			: GuestLayout
	})
</script>
