import { usePage } from '@inertiajs/react';

import type { SharedProps } from '@/types';

/** Typed access to props shared by HandleInertiaRequests on every page. */
export function useShared(): SharedProps {
    return usePage<SharedProps>().props;
}

export function useCan(permission: string): boolean {
    return useShared().auth?.user?.permissions.includes(permission) ?? false;
}
