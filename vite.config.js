import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { viteStaticCopy } from 'vite-plugin-static-copy';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/sass/nobleui/app.scss',
                'resources/css/nobleui-custom.css',
                'resources/js/app.js',
                'resources/js/nobleui/template.js',
                'resources/js/nobleui/color-modes.js',
            ],
            refresh: true,
        }),
        viteStaticCopy({
            targets: [
                {
                    src: 'node_modules/bootstrap/dist/js/bootstrap.bundle.min.js',
                    dest: 'nobleui/plugins/bootstrap',
                },
                {
                    src: 'node_modules/lucide/dist/umd/lucide.min.js',
                    dest: 'nobleui/plugins/lucide',
                },
                {
                    src: ['node_modules/perfect-scrollbar/dist/perfect-scrollbar.min.js', 'node_modules/perfect-scrollbar/css/perfect-scrollbar.css'],
                    dest: 'nobleui/plugins/perfect-scrollbar',
                },
                {
                    src: 'node_modules/flag-icons/flags/4x3',
                    dest: 'nobleui/plugins/flag-icons/flags',
                },
            ],
        }),
    ],
    build: {
        cssMinify: true,
        minify: 'esbuild',
        rollupOptions: {
            output: {
                manualChunks: undefined,
            },
        },
    },
    css: {
        devSourcemap: true,
        preprocessorOptions: {
            scss: {
                silenceDeprecations: ['color-functions', 'global-builtin', 'import', 'if-function'],
            },
        },
    },
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
