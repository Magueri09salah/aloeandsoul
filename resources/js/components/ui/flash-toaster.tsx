import { router, usePage } from '@inertiajs/react';
import { useEffect, useRef } from 'react';
import { Toaster, toast } from 'sonner';

import type { SharedProps } from '@/types';

function show(flash: SharedProps['flash'] | undefined) {
    if (flash?.success) toast.success(flash.success);
    if (flash?.error) toast.error(flash.error);
}

/**
 * Shows the server's flash messages as toasts: once for the first page load,
 * then once per successful navigation.
 */
export function FlashToaster() {
    const initial = usePage<SharedProps>().props.flash;
    const shownInitial = useRef(false);

    useEffect(() => {
        if (!shownInitial.current) {
            shownInitial.current = true;
            show(initial);
        }

        return router.on('success', (event) => show((event.detail.page.props as unknown as SharedProps).flash));
        // eslint-disable-next-line react-hooks/exhaustive-deps -- initial flash only
    }, []);

    return (
        <Toaster
            position="bottom-center"
            toastOptions={{
                classNames: {
                    toast: '!rounded-lg !border-line !bg-ink !text-ivory !font-sans !shadow-overlay',
                    description: '!text-ivory/80',
                },
            }}
        />
    );
}
