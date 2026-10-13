import { Link, usePage } from '@inertiajs/react';

import { Button } from '@/components/ui/button';

const MESSAGES: Record<number, { title: string; text: string }> = {
    403: { title: 'You don’t have access to this page.', text: 'If you think you should, ask the shop owner to check your account.' },
    404: { title: 'This page doesn’t exist.', text: 'It may have moved, or the address may be mistyped.' },
    410: { title: 'This page has been removed.', text: 'The item is no longer available.' },
    429: { title: 'Too many requests.', text: 'Please wait a minute and try again.' },
    500: { title: 'Something went wrong on our side.', text: 'Please try again in a moment. If it keeps happening, contact us and quote the reference below.' },
    503: { title: 'We’ll be right back.', text: 'The shop is being updated. Please try again in a few minutes.' },
};

export default function ErrorPage({ status, request_id }: { status: number; request_id?: string | null }) {
    const { url } = usePage();
    const message = MESSAGES[status] ?? MESSAGES[500]!;
    const isAdmin = url.startsWith('/admin');

    return (
        <section className="container-page max-w-2xl py-24 text-center">
            <p className="font-display text-6xl text-dune">{status}</p>
            <h1 className="mt-4 font-display text-display-sm">{message.title}</h1>
            <p className="mt-4 text-lg text-stone">{message.text}</p>
            {status >= 500 && request_id && <p className="mt-4 text-sm text-stone">Reference: {request_id}</p>}
            <div className="mt-10 flex flex-wrap justify-center gap-3">
                <Button asChild>
                    <Link href={isAdmin ? '/admin' : '/'}>{isAdmin ? 'Back to the dashboard' : 'Go to the home page'}</Link>
                </Button>
                {!isAdmin && (
                    <Button asChild variant="secondary">
                        <Link href="/shop">Browse the shop</Link>
                    </Button>
                )}
            </div>
        </section>
    );
}
