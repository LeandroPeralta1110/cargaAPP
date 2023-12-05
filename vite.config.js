import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    server: {
        // Cambia la dirección a la dirección IP de tu servidor y el puerto de Vite
        host: '192.168.0.127', // Cambia a la dirección IP de tu servidor
        port: 5174, // Cambia al puerto que estás utilizando
    },
    plugins: [
        laravel({
            input: [
                'resources/sass/app.scss',
                'resources/js/app.js',
            ],
            refresh: true,
        }),
    ],
});
