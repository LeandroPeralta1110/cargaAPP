import { defineConfig } from 'vite';
import laravel, { refreshPaths } from 'laravel-vite-plugin';

export default defineConfig({
    server: {
        // Cambia la dirección a la dirección IP de tu servidor y el puerto de Vite
        host: '192.168.0.118', // Cambia a la dirección IP de tu servidor
        port: 5174, // Cambia al puerto que estás utilizando
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
