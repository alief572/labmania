import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    // VITE_BASE (mis. /labmania/build/) ditanam saat build untuk deployment subpath.
    ...(process.env.VITE_BASE ? { base: process.env.VITE_BASE } : {}),
    plugins: [laravel({ input: ['resources/js/app.ts'], refresh: true }), vue()],
});
