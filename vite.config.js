import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { copyFileSync, existsSync } from 'fs';
import { resolve } from 'path';

const mediapipeFiles = [
    'face_detection_short.binarypb',
    'face_detection_short_range.tflite',
    'face_detection_solution_simd_wasm_bin.js',
    'face_detection_solution_simd_wasm_bin.wasm',
    'face_detection_solution_wasm_bin.js',
    'face_detection_solution_wasm_bin.wasm',
];

const ensureMediapipeAssets = () => {
    const root = process.cwd();
    const srcDir = resolve(root, 'node_modules', '@mediapipe', 'face_detection');
    const publicDir = resolve(root, 'public');

    mediapipeFiles.forEach((file) => {
        const src = resolve(srcDir, file);
        const dest = resolve(publicDir, file);
        if (existsSync(dest) || !existsSync(src)) {
            return;
        }
        copyFileSync(src, dest);
    });
};

ensureMediapipeAssets();

export default defineConfig({

    /* Pruebas */
    /* base: '/gestion/autogestion2/public/build/', */

    /* Produccion */
    base: '/gestion360/public/build/',

    plugins: [
        laravel({
            input: ['resources/css/app.css',
                    'resources/js/notificaciones.js',
                    'resources/css/custom.css',
                    'resources/css/huellero.css',
                    'resources/css/mobile.css',
                    'resources/js/app.js',
                    'resources/js/cargarModal.js',
                    'resources/js/camara/enroll.js',
                    'resources/js/camara/recognize.js',
                    'resources/js/camara/verify.js',
                ],
            refresh: true,
        }),
    ],
    assetsInclude: ['**/*.wasm', '**/*.tflite', '**/*.binarypb'],
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
