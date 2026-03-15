// Author: MEMORA solutions, https://memora.solutions ; info@memora.ca
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { viteStaticCopy } from 'vite-plugin-static-copy';
import { VitePWA } from 'vite-plugin-pwa';

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
                    src: ['node_modules/bootstrap/dist/js/bootstrap.bundle.min.js', 'node_modules/bootstrap/dist/css/bootstrap.min.css'],
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
                {
                    src: ['node_modules/tom-select/dist/js/tom-select.complete.min.js', 'node_modules/tom-select/dist/css/tom-select.bootstrap5.min.css'],
                    dest: 'nobleui/plugins/tom-select',
                },
                {
                    src: 'node_modules/sortablejs/Sortable.min.js',
                    dest: 'nobleui/plugins/sortablejs',
                },
                {
                    src: 'node_modules/fullcalendar/index.global.min.js',
                    dest: 'nobleui/plugins/fullcalendar',
                },
                {
                    src: ['node_modules/driver.js/dist/driver.js.iife.js', 'node_modules/driver.js/dist/driver.css'],
                    dest: 'nobleui/plugins/driver',
                },
                {
                    src: ['node_modules/bootstrap-icons/font/bootstrap-icons.min.css', 'node_modules/bootstrap-icons/font/fonts'],
                    dest: 'nobleui/plugins/bootstrap-icons',
                },
            ],
        }),
        VitePWA({
            strategies: 'injectManifest',
            srcDir: 'resources/js',
            filename: 'sw-source.js',
            registerType: 'prompt',
            manifest: false,
            injectManifest: {
                globPatterns: ['**/*.{js,css,ico,png,svg,woff2}'],
            },
            devOptions: {
                enabled: false,
            },
        }),
    ],
    build: {
        cssMinify: true,
        minify: 'esbuild',
        rollupOptions: {
            output: {
                manualChunks(id) {
                    if (id.includes('node_modules')) {
                        if (id.includes('@tiptap') || id.includes('prosemirror')) {
                            return 'editor';
                        }
                        return 'vendor';
                    }
                },
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
