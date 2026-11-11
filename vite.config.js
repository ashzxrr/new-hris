import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';


export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js', 'resources/js/turnover.js', 'resources/js/salary-config.js'],
            refresh: true,
        }),
        tailwindcss(),
    ],
});
