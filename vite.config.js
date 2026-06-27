import { defineConfig, loadEnv } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig(({ mode }) => {
    const env = loadEnv(mode, process.cwd(), '');
    const appUrl = env.APP_URL ? new URL(env.APP_URL) : null;

    return {
        plugins: [
            laravel({
                input: ['resources/css/app.css', 'resources/js/app.js'],
                refresh: true,
            }),
            tailwindcss(),
        ],
        server: {
            host: true,
            hmr: appUrl ? { host: appUrl.hostname } : undefined,
            watch: {
                ignored: ['**/storage/framework/views/**'],
            },
        },
    };
});
