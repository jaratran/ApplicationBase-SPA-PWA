/**
 * Convierte un color HEX (#RRGGBB) a formato RGB (r,g,b)
 * (EcoRuta hacía esto indirectamente en Blade al generar RGB desde PHP)
 */
function hexToRgb(hex) {
  if (!hex) return null;
  const cleanHex = hex.replace('#', '');
  const bigint = parseInt(cleanHex, 16);
  const r = (bigint >> 16) & 255;
  const g = (bigint >> 8) & 255;
  const b = bigint & 255;
  return `${r}, ${g}, ${b}`;
}

/**
 * Carga los parámetros de diseño desde la API Laravel
 * y los aplica como variables CSS globales.
 *
 * (Equivalente a cómo EcoRuta definía el bloque <style> :root {...})
 */
export async function applyDesignParameters() {
	let data = null;

	try {
		// Intentamos obtener desde red o desde Service Worker
		const response = await fetch('/api/design-parameters').catch(() => null);
		if (!response) {
			throw new Error("No se pudo obtener parámetros de diseño - Sin conexión y sin fallback SW");
		}

		data = await response.json();

		// Guardar en localStorage solo la primera vez
		const canUseLocalStorage = typeof window !== 'undefined' &&								// Helper para saber si podemos usar localStorage
									typeof window.localStorage !== 'undefined';					// y así comunicarle al ServiceWorkerlos parámetros de diseño

		if (canUseLocalStorage && !window.localStorage.getItem('designParameters')) {			// Si podemos usar el localStorage y aún no guardamos designParameters ...
			try {
				window.localStorage.setItem('designParameters', JSON.stringify(data));
			} catch (e) {
				console.warn('⚠️ No se pudieron guardar los parámetros de diseño en localStorage:', e);
			}
		}

	} catch (error) {
		console.error("⚠️ No se pudieron obtener parámetros de diseño:", error);
		return; // evitar aplicar estilos vacíos
	}

	// ====================================================
	// 🔥 Solo desde aquí hacia abajo se aplican los colores
	// ====================================================

    // 🧩 1. Aplicar colores personalizados
    Object.entries(data).forEach(([key, value]) => {
      if (key.startsWith('custom_') && value) {
        const varName = '--bs-' + key.replace('custom_', '');
        document.documentElement.style.setProperty(varName, value);

        // Versión RGB (para transparencias, sombras, etc.)
        const rgb = hexToRgb(value);
        if (rgb) {
          document.documentElement.style.setProperty(`${varName}-rgb`, rgb);
        }
      }
    });

    // 🧩 2. Aplicar rutas de imágenes de marca
    if (data.logo_design) {
      document.documentElement.style.setProperty('--logo-design', `url('/config/${data.logo_design}')`);
    }

    if (data.emblema_design) {
      document.documentElement.style.setProperty('--emblema-design', `url('/config/${data.emblema_design}')`);
    }

    // ============================================================
    // ⭐ NUEVO → Actualizar el título dinámicamente (como Blade)
    // ============================================================
    if (data.titulo_design) {
      document.title = data.titulo_design;
    }

    // ============================================================
    // ⭐ NUEVO → Reemplazar correctamente el FAVICON
    // ============================================================
    if (data.favicon_design) {
      const faviconPath = `/config/${data.favicon_design}`;

      // 1. Actualiza variable CSS (por compatibilidad con tu código actual)
      document.documentElement.style.setProperty('--favicon-design', `url('${faviconPath}')`);

      // 2. Elimina todos los <link rel="icon"> existentes
      const existingIcons = document.querySelectorAll("link[rel='icon'], link[rel='shortcut icon']");
      existingIcons.forEach(link => link.parentNode.removeChild(link));

      // 3. Crea uno nuevo idéntico a como lo hacía Blade
      const newFavicon = document.createElement('link');
      newFavicon.rel = 'icon';
      newFavicon.href = faviconPath;
      document.head.appendChild(newFavicon);
    }

    if (data.fondo_pantalla_design) {
      document.documentElement.style.setProperty('--background-design', `url('/config/${data.fondo_pantalla_design}')`);
    }

    // 🧩 3. Opcionalmente, podríamos exponer un objeto global si el layout o el login lo requieren
    window.DesignParameters = data;

	// 🔔 4. Notificar que los parámetros de diseño ya fueron aplicados
	window.dispatchEvent(new Event('design-parameters-applied'));

}
