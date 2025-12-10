// vite.config.js

import { defineConfig } from 'vite'
import laravel                  from 'laravel-vite-plugin'
import vue                      from '@vitejs/plugin-vue'
import tailwindcss              from '@tailwindcss/vite'
import fs                       from 'fs'
import path                     from 'path'

// Plugin simple para copiar el service worker al build final
function copyServiceWorker() {
	return {
		name: 'copy-service-worker',
		closeBundle() {
			const source = path.resolve('public/service-worker.js');
			const destDir = path.resolve('public/build');
			const dest = path.resolve(destDir, 'service-worker.js');

			// Crear carpeta public/build si no existe
			if (!fs.existsSync(destDir)) {
				fs.mkdirSync(destDir, { recursive: true });
			}

			// Copiar service-worker.js
			if (fs.existsSync(source)) {
				fs.copyFileSync(source, dest);
				console.log('✔ service-worker.js copiado al build');
			} else {
				console.warn('⚠ No existe public/service-worker.js — se omitió la copia');
			}
		}
	};
}

export default defineConfig({
	publicDir: 'public',    // 👈 asegúrate que Vite copie public/ completo

	plugins: [
        vue(),
        laravel({
			input: [
						'resources/css/bootstrap-sim.css',
                        'resources/css/app.css',
                        'resources/js/app.js',
                        'resources/js/frontend/main.js',
                    ],
            refresh: true,
        }),
        tailwindcss(), // 👈 vuelve a activarse
		copyServiceWorker(),   // 👈 agrega el plugin de copia

	],
})
