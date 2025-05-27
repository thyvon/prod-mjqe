import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    plugins: [
        // Laravel Vite plugin for handling Laravel-specific tasks
        laravel({
            input: [
                'resources/js/app.js',
            ],
            refresh: true,
        }),
        // Vue plugin for handling Vue.js files
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
    server: {
        watch: {
            // Exclude the public/coloradmin directory to reduce watch load
            ignored: [
                '**/public/coloradmin/**',
                '**/public/documentation/**'
            ],
        },
    },
    resolve: {
        alias: {
            // Add aliases for cleaner imports
            '@': '/resources/js',
        },
    },
});
