import { defineConfig } from 'vite'
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
	root: 'public',              // 👈 CLAVE
	base: '/build/',             // 👈 rutas absolutas
	publicDir: false,            // 👈 ya estamos en public

	plugins: [
		vue(),
		tailwindcss(),			// 👈 vuelve a activarse
		copyServiceWorker()		// 👈 agrega el plugin de copia
	],

	build: {
		manifest: true,
		outDir: 'build',            // → public/build
		emptyOutDir: true,
		rollupOptions: {
			input: '/index.html'      // 👈 ENTRY REAL
		}
	},

	/**
	 * ⚠️ Sección SOLO para modo desarrollo (npm run dev)
	 * Mantenerla como documentación técnica. No afecta producción porque Vite Dev Server no corre en ese entorno.
	 */
	server: {
		host: 'calidad.hp-notebook.cl',
		port: 5173,
		https: {
			key: fs.readFileSync('C:/xampp-8.2.12/apache/conf/ssl.key/calidad.key'),
			cert: fs.readFileSync('C:/xampp-8.2.12/apache/conf/ssl.crt/calidad.crt'),
		},
	},
})
