import path from 'path'; 
import { defineConfig } from 'vite';

export default defineConfig({ 
    resolve: {
        alias: {
            '@lara-gatekeeper': path.resolve(__dirname, './resources/js'), 
        },
    },
});
