import { defineStore } from 'pinia'
import { useNetworkStore } from './network'
import { getCachedDashboard, setCachedDashboard } from '@/services/offline/dashboardCacheRepo'
import api from '@/services/api'

export const usePanelStore = defineStore('panel', {
	state: () => ({
		data: null,
		loading: false,
		error: null
	}),

	actions: {
		async fetchPanel() {
			const network = useNetworkStore()
			this.loading = true
			this.error = null

			// 🔌 OFFLINE → cache
			if (!network.isOnline) {
				const cached = await getCachedDashboard()
				if (cached?.data) {
					this.data = cached.data
					this.loading = false
					return this.data
				}

				this.loading = false
				this.error = 'offline-no-cache'
				return null
			}

			// 🌐 ONLINE → API
			try {
				const response = await api.get('/panel/datos')

				if (response.data?.status === 'success') {
					this.data = response.data.data
					await setCachedDashboard(this.data)
					return this.data
				}

				this.error = response.data?.message || 'error'
				return null

			} catch (error) {
				console.warn('[panel] Error API, usando cache', error)

				// fallback cache
				const cached = await getCachedDashboard()
				if (cached?.data) {
					this.data = cached.data
					return this.data
				}

				this.error = 'network-error'
				return null

			} finally {
				this.loading = false
			}
		}
	}
})
