import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue2';
import path from 'path';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        vue({
            template: {
                compilerOptions: {
                    isCustomElement: tag => tag.startsWith('ion-')
                }
            }
        }),
        tailwindcss(),
    ],
    resolve: {
        alias: {
            'vue': 'vue/dist/vue.esm.js',
            '@': path.resolve(__dirname, './resources/js')
        }
    }
});
