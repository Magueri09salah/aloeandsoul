import { cva, type VariantProps } from 'class-variance-authority';
import { X } from 'lucide-react';
import { Dialog as DialogPrimitive } from 'radix-ui';
import * as React from 'react';

import { useI18n } from '@/lib/i18n';
import { cn } from '@/lib/utils';

const Sheet = DialogPrimitive.Root;
const SheetTrigger = DialogPrimitive.Trigger;
const SheetClose = DialogPrimitive.Close;

const sheetVariants = cva(
    'fixed z-50 flex flex-col bg-ivory shadow-overlay outline-none data-[state=closed]:duration-300 data-[state=open]:duration-400 data-[state=open]:animate-in data-[state=closed]:animate-out',
    {
        variants: {
            side: {
                right: 'inset-y-0 right-0 h-full w-full max-w-[26rem] data-[state=closed]:slide-out-to-right data-[state=open]:slide-in-from-right',
                left: 'inset-y-0 left-0 h-full w-[88%] max-w-sm data-[state=closed]:slide-out-to-left data-[state=open]:slide-in-from-left',
                bottom: 'inset-x-0 bottom-0 max-h-[88dvh] rounded-t-2xl data-[state=closed]:slide-out-to-bottom data-[state=open]:slide-in-from-bottom',
            },
        },
        defaultVariants: { side: 'right' },
    },
);

interface SheetContentProps extends React.ComponentProps<typeof DialogPrimitive.Content>, VariantProps<typeof sheetVariants> {
    title: string;
    description?: string;
    hideTitle?: boolean;
}

function SheetContent({ side, className, children, title, description, hideTitle, ...props }: SheetContentProps) {
    const { t } = useI18n();

    return (
        <DialogPrimitive.Portal>
            <DialogPrimitive.Overlay className="fixed inset-0 z-50 bg-ink/35 data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:animate-in data-[state=open]:fade-in-0" />
            <DialogPrimitive.Content className={cn(sheetVariants({ side }), className)} {...props}>
                <div className={cn('flex items-center justify-between gap-4 border-b border-line px-5 py-4', hideTitle && 'sr-only')}>
                    <DialogPrimitive.Title className="font-display text-2xl">{title}</DialogPrimitive.Title>
                    <DialogPrimitive.Close className="-mr-2 inline-flex size-10 items-center justify-center rounded-full hover:bg-sand">
                        <X className="size-5" aria-hidden="true" />
                        <span className="sr-only">{t('Close')}</span>
                    </DialogPrimitive.Close>
                </div>
                {description ? (
                    <DialogPrimitive.Description className="sr-only">{description}</DialogPrimitive.Description>
                ) : (
                    <DialogPrimitive.Description className="sr-only">{title}</DialogPrimitive.Description>
                )}
                {children}
            </DialogPrimitive.Content>
        </DialogPrimitive.Portal>
    );
}

export { Sheet, SheetClose, SheetContent, SheetTrigger };
