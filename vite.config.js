import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';
import { VitePWA } from 'vite-plugin-pwa';

export default defineConfig({
    server: {
        host: '127.0.0.1',
    },
    plugins: [
        laravel({
            input: 'resources/js/app.jsx',
            refresh: true,
        }),
        react(),
        VitePWA({
            strategies: 'generateSW',
            registerType: 'autoUpdate',
            injectRegister: false,
            outDir: 'public',
            base: '/',
            scope: '/',
            manifest: false,
            workbox: {
                globPatterns: ['**/*.{js,css,ico,png,svg,woff2}'],
                maximumFileSizeToCacheInBytes: 5 * 1024 * 1024,
                navigateFallback: null,
            },
            devOptions: {
                enabled: true,
            },
        }),
    ],
});
