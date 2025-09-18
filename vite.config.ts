import path from 'path'; 
import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';

export default defineConfig({ 
    plugins: [
        vue(),
    ],
    resolve: {
        alias: {
            '@lara-gatekeeper': path.resolve(__dirname, './resources/js'), 
        },
    },
    build: {
        lib: {
            entry: path.resolve(__dirname, 'resources/js/index.ts'),
            name: 'LaraGatekeeper',
            fileName: (format) => `lara-gatekeeper.${format}.js`,
        },
        rollupOptions: {
            external: ['vue'],
            output: {
                globals: {
                    vue: 'Vue',
                },
            },
        },
        outDir: 'dist',
        sourcemap: true,
    },
});
