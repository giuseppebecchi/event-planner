import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';

export default defineConfig({
    server: {
        host: '0.0.0.0',
        port: 5173,
        strictPort: true,
        origin: 'http://localhost:5173',
        cors: {
            origin: ['http://localhost:8080'],
        },
        hmr: {
            host: 'localhost',
        },
    },
    plugins: [
        react(),
        laravel({
            input: ['resources/css/app.css', 'resources/css/filament/admin/theme.css', 'resources/js/app.jsx'],
            refresh: true,
        }),
    ],
});
