import { defineStore } from 'pinia'

export const useNetworkStore = defineStore('network', {
	state: () => {
		const online = typeof navigator !== 'undefined'
			? navigator.onLine
			: true

		return {
			isOnline: online,
			lastOnlineAt: online ? new Date().toISOString() : null
		}
	},

	actions: {
		setOnline() {
			this.isOnline = true
			this.lastOnlineAt = new Date().toISOString()
		},

		setOffline() {
			this.isOnline = false
		}
	}
})
