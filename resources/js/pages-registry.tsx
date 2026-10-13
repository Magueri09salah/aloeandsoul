import type { Page } from '@inertiajs/core';
import { lazy, Suspense, type ComponentType, type ReactNode } from 'react';

import AuthLayout from '@/layouts/auth-layout';
import StoreLayout from '@/layouts/store-layout';

type PageModule = { default: ComponentType };
type Layout = ComponentType<{ children: ReactNode }>;

// Each page is its own chunk: storefront visitors never download admin code.
const pages = import.meta.glob<PageModule>('./pages/**/*.tsx');

// The admin shell is loaded on demand, for the same reason.
const LazyAdminLayout = lazy(() => import('@/layouts/admin-layout'));
function AdminShell({ children }: { children: ReactNode }) {
    return (
        <Suspense fallback={null}>
            <LazyAdminLayout>{children}</LazyAdminLayout>
        </Suspense>
    );
}

export async function resolvePage(name: string): Promise<ComponentType> {
    const loader = pages[`./pages/${name}.tsx`];
    if (!loader) {
        throw new Error(`Page not found: ${name}`);
    }
    return (await loader()).default;
}

/** Admin pages get the admin shell, sign-in pages a minimal one, everything else the storefront. */
export function resolveLayout(name: string, page?: Page): Layout {
    if (name.startsWith('admin/auth/')) return AuthLayout;
    if (name.startsWith('admin/')) return AdminShell;
    if (name === 'error' && page?.url.startsWith('/admin')) return AuthLayout;
    return StoreLayout;
}
