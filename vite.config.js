import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js', 
                'resources/css/filament/sys/theme.css', 
                'resources/css/filament/home/theme.css'],
            refresh: true,
        }),
    ],
});
