import { defineConfig } from 'vite';
import laravel, { refreshPaths } from 'laravel-vite-plugin';

export default defineConfig({
    server: {
        // Cambia la dirección a la dirección IP de tu servidor y el puerto de Vite
        host: '172.10.10.80', // Cambia a la dirección IP de tu servidor
        port: 5173, // Cambia al puerto que estás utilizando
    },
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
            ],
            refresh: [
                ...refreshPaths,
                'app/Http/Livewire/**',
            ],
        }),
    ],
});