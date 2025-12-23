<template>
	<aside
		class="fixed top-12 left-0 z-50 w-64 h-[calc(100vh-3rem)] bg-white border-r shadow-lg transform transition-transform duration-200"
		:class="{
		'-translate-x-64': !open,
		'translate-x-0': open
	}">
		<div class="h-12 flex items-center px-4 border-b">
			<span class="font-semibold text-sm text-gray-700">Menú principal</span>
		</div>

		<nav class="p-4 text-sm text-gray-600">
			<p class="text-gray-400 italic">Menú en construcción…</p>
		</nav>
	</aside>
</template>

<script setup>
	defineProps({
		open: Boolean
	})
</script>
