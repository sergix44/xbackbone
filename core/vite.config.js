import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from "@tailwindcss/vite";

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        tailwindcss(),
    ],
    build: {
        rolldownOptions: {
            output: {
                codeSplitting: {
                    minSize: 20000,
                    groups: [
                        {
                            name: 'vendor',
                            test: /node_modules/,
                        },
                    ],
                },
            },
        }
    }
});
