import { createContext, useContext, useMemo, useState, type ReactNode } from 'react';

interface CartUi {
    isOpen: boolean;
    open: () => void;
    setOpen: (open: boolean) => void;
}

const CartUiContext = createContext<CartUi | null>(null);

/** Cart drawer state, kept by the persistent storefront layout across visits. */
export function CartUiProvider({ children }: { children: ReactNode }) {
    const [isOpen, setOpen] = useState(false);
    const value = useMemo(() => ({ isOpen, setOpen, open: () => setOpen(true) }), [isOpen]);

    return <CartUiContext.Provider value={value}>{children}</CartUiContext.Provider>;
}

export function useCartUi(): CartUi {
    const ctx = useContext(CartUiContext);
    if (!ctx) throw new Error('useCartUi must be used inside <CartUiProvider>');
    return ctx;
}
