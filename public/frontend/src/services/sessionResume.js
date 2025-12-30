/**
 * sessionResume.js
 * --------------------------------------------------
 * Decisión centralizada para reanudar una sesión
 * previamente no cerrada (online u offline).
 *
 * NO:
 *  - No toca UI
 *  - No navega directamente
 *  - No modifica stores
 *
 * SÍ:
 *  - Decide si existe sesión reanudable
 *  - Indica el modo de reanudación esperado
 */

import { useOfflineIdentityStore } from '@/stores/offlineIdentity'
import { useNetworkStore } from '@/stores/network'

export function resolveSessionResume() {
	const network = useNetworkStore()
	const offlineIdentity = useOfflineIdentityStore()

	offlineIdentity.loadFromStorage()

	// No hay identidad persistida → no hay sesión que reanudar
	if (!offlineIdentity.hasIdentity) {
		return {
			canResume: false,
			mode: null,           // 'online' | 'offline'
			reason: 'no-identity'
		}
	}

	// Hay identidad persistida
	if (network.isOnline) {
		return {
			canResume: true,
			mode: 'online',
			reason: 'identity-present-online'
		}
	}

	// Offline + identidad persistida
	if (offlineIdentity.isEnabled) {
		return {
			canResume: true,
			mode: 'offline',
			reason: 'identity-present-offline'
		}
	}

	// Caso residual (seguridad)
	return {
		canResume: false,
		mode: null,
		reason: 'identity-not-enabled'
	}
}
