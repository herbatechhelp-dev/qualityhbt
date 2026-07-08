import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    plugins: [
        laravel({
            input: 'resources/js/app.js',
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
    build: {
        rollupOptions: {
            output: {
                sanitizeFileName(name) {
                    const match = /^[a-z]:/i.exec(name);
                    const driveLetter = match ? match[0] : '';
                    return (
                        driveLetter +
                        name.slice(driveLetter.length)
                            .replace(/[\x00-\x1F\x7F<>*#{}|^[\]`;?:&=+$,]/g, '')
                            .replace(/^_/, '')
                    );
                },
            },
        },
    },
});
