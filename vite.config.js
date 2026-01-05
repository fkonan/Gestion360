import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({

   /* base: '/gestion/autogestion2/public/build/',  */
    base: '/gestion/public/build/',

    plugins: [
        laravel({
            input: ['resources/css/app.css',
                    'resources/css/custom.css',
                    'resources/css/mobile.css',
                    'resources/js/app.js',
                    'resources/js/cargarModal.js',
                    'resources/js/utils.js',
                    // Scripts del sistema de huellas
                    'resources/js/huellero/laravel-fingerprint-sdk.js',
                    'resources/js/huellero/integration-bridge.js',
                    'resources/js/huellero/dashboard.js',
                    'resources/js/huellero/captura.js',
                    'resources/js/huellero/verificacion.js',
                ],
            refresh: true,
        }),
    ],
});
