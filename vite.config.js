import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({

    /* Pruebas */
    base: '/gestion/autogestion2/public/build/',

    /* Produccion */
    /* base: '/gestion/public/build/', */

    plugins: [
        laravel({
            input: ['resources/css/app.css',
                    'resources/js/notificaciones.js',
                    'resources/css/custom.css',
                    'resources/css/mobile.css',
                    'resources/js/app.js',
                    'resources/js/cargarModal.js',
                ],
            refresh: true,
        }),
    ],
    build: {
        rollupOptions: {
            output: {
                manualChunks(id) {
                    if (!id.includes('node_modules')) {
                        return;
                    }
                    if (id.includes('xlsx')) return 'xlsx';
                    if (id.includes('bootstrap-table')) return 'vendor';
                    if (id.includes('jquery')) return 'vendor';
                    if (id.includes('jquery-validation')) return 'vendor';
                    if (id.includes('select2')) return 'select2';
                    if (id.includes('sweetalert2')) return 'sweetalert2';
                    if (id.includes('bootstrap')) return 'bootstrap';
                    return 'vendor';
                },
            },
        },
    },
});
