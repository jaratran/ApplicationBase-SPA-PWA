import { defineConfig } from 'vite'
import laravel from 'laravel-vite-plugin'
import vue from '@vitejs/plugin-vue'
import tailwindcss from '@tailwindcss/vite'
import fs from 'fs'

export default defineConfig({
    plugins: [
        vue(),
        laravel({
            input: ['resources/css/app.css',
                    'resources/js/app.js',
                    'resources/js/frontend/main.js', // 👈 importante    
                ],
            refresh: true,
        }),
        tailwindcss(),
    ],
    server: {
        host: 'calidad.hp-notebook.cl',
        port: 5173,
        https: {
            key: fs.readFileSync('C:/xampp-8.2.12/apache/conf/ssl.key/calidad.key'),
            cert: fs.readFileSync('C:/xampp-8.2.12/apache/conf/ssl.crt/calidad.crt'),
        },
    },
})
