/**
 * Optional server-side rendering entry. Only used when the host can run a
 * persistent Node process (INERTIA_SSR_ENABLED=true + `php artisan inertia:start-ssr`).
 * SEO-critical meta tags are rendered by Blade either way.
 */
import { createInertiaApp } from '@inertiajs/react';
import createServer from '@inertiajs/react/server';
import ReactDOMServer from 'react-dom/server';

import { resolveLayout, resolvePage } from '@/pages-registry';

createServer((page) =>
    createInertiaApp({
        page,
        render: ReactDOMServer.renderToString,
        resolve: resolvePage,
        layout: resolveLayout,
        setup: ({ App, props }) => <App {...props} />,
    }),
);
