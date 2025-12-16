<!-- resources/js/frontend/src/components/AlertSystem.vue -->

<template>
	<div v-if="alert.messages.length" :class="{	'alert alert-success': alert.type === 'success',
												'alert alert-danger': alert.type === 'error',
												'alert alert-warning': alert.type === 'warning',
												'alert alert-info': alert.type === 'info' }">
		<ul class="mb-0">
			<li v-for="(m, index) in alert.messages" :key="index">{{ m }}</li>
		</ul>
	</div>
</template>

<script setup>
	import { useAlertStore } from '../stores/alert'

	const alert = useAlertStore()
</script>
