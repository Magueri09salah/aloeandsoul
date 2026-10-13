import '../css/app.css';

import { createInertiaApp } from '@inertiajs/react';
import { createRoot, hydrateRoot } from 'react-dom/client';

import { resolveLayout, resolvePage } from '@/pages-registry';

createInertiaApp({
    resolve: resolvePage,
    layout: resolveLayout,
    progress: { color: '#4A5E4C', delay: 200, showSpinner: false },
    setup({ el, App, props }) {
        // Hydrate when the page was server-rendered (optional SSR), otherwise mount.
        if (el.hasChildNodes()) {
            hydrateRoot(el, <App {...props} />);
        } else {
            createRoot(el).render(<App {...props} />);
        }
    },
});
