import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({

    base: '/gestion/autogestion2/public/build/',

    plugins: [
        laravel({
            input: ['resources/css/app.css', 
                    'resources/css/custom.css',
                    'resources/css/mobile.css',
                    'resources/css/darkmode.css',
                    'resources/js/app.js',
                    'resources/js/cargarModal.js',
                    'resources/js/utils.js',
                ],
            refresh: true,
        }),
    ],
});
