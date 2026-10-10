import react from '@vitejs/plugin-react';
import Icons from 'unplugin-icons/vite';
import type { PluginOption } from 'vite';
import { saucebase as core } from '../vite.ts';

/**
 * The Saucebase build for a React app: `plugins: [saucebase()]`.
 */
export function saucebase(): PluginOption[] {
    return [
        core({ framework: 'react', entry: 'resources/js/app.tsx' }),
        react(),
        Icons({ compiler: 'jsx', jsx: 'react' }),
    ];
}
