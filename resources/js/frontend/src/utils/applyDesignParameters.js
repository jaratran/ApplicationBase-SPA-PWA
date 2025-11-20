// resources/js/frontend/src/utils/applyDesignParameters.js

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

  try {
    const response = await fetch('/api/design-parameters');
    if (!response.ok) throw new Error('Error al obtener parámetros de diseño');

    const data = await response.json();

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

  } catch (error) {
    console.error('⚠️ No se pudieron aplicar los parámetros de diseño:', error);
  }
}
