import { Head } from '@inertiajs/react';
import { type ReactNode } from 'react';

import { Logo } from '@/components/brand/logo';
import { FlashToaster } from '@/components/ui/flash-toaster';

/** Minimal shell for admin sign-in, password reset and two-factor pages. */
export default function AuthLayout({ children }: { children: ReactNode }) {
    return (
        <div className="flex min-h-dvh flex-col items-center justify-center bg-ivory px-4 py-12">
            <Head>
                <meta head-key="robots" name="robots" content="noindex,nofollow" />
            </Head>
            <Logo href="/" className="mb-10" />
            <main className="w-full max-w-md">{children}</main>
            <FlashToaster />
        </div>
    );
}
