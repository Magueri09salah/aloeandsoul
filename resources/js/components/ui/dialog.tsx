import { X } from 'lucide-react';
import { AlertDialog as AlertPrimitive, Dialog as DialogPrimitive } from 'radix-ui';
import * as React from 'react';

import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';

const overlay =
    'fixed inset-0 z-50 bg-ink/35 data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:animate-in data-[state=open]:fade-in-0';
const panel =
    'fixed top-1/2 left-1/2 z-50 w-[calc(100%-2rem)] max-w-lg -translate-x-1/2 -translate-y-1/2 rounded-lg bg-white p-6 shadow-overlay outline-none data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=closed]:zoom-out-95 data-[state=open]:animate-in data-[state=open]:fade-in-0 data-[state=open]:zoom-in-95';

const Dialog = DialogPrimitive.Root;
const DialogTrigger = DialogPrimitive.Trigger;
const DialogClose = DialogPrimitive.Close;

function DialogContent({
    title,
    description,
    className,
    children,
    ...props
}: React.ComponentProps<typeof DialogPrimitive.Content> & { title: string; description?: string }) {
    return (
        <DialogPrimitive.Portal>
            <DialogPrimitive.Overlay className={overlay} />
            <DialogPrimitive.Content className={cn(panel, 'max-h-[90dvh] overflow-y-auto', className)} {...props}>
                <div className="mb-4 flex items-start justify-between gap-4">
                    <div>
                        <DialogPrimitive.Title className="font-display text-2xl leading-tight">{title}</DialogPrimitive.Title>
                        <DialogPrimitive.Description className={cn('mt-1 text-sm text-stone', !description && 'sr-only')}>
                            {description ?? title}
                        </DialogPrimitive.Description>
                    </div>
                    <DialogPrimitive.Close className="-mt-1 -mr-2 inline-flex size-9 items-center justify-center rounded-full hover:bg-sand">
                        <X className="size-4" aria-hidden="true" />
                        <span className="sr-only">Close</span>
                    </DialogPrimitive.Close>
                </div>
                {children}
            </DialogPrimitive.Content>
        </DialogPrimitive.Portal>
    );
}

interface ConfirmDialogProps {
    trigger: React.ReactNode;
    title: string;
    description: React.ReactNode;
    confirmLabel: string;
    destructive?: boolean;
    onConfirm: () => void;
    processing?: boolean;
}

/** Confirmation before destructive or irreversible admin actions. */
function ConfirmDialog({ trigger, title, description, confirmLabel, destructive = true, onConfirm, processing }: ConfirmDialogProps) {
    return (
        <AlertPrimitive.Root>
            <AlertPrimitive.Trigger asChild>{trigger}</AlertPrimitive.Trigger>
            <AlertPrimitive.Portal>
                <AlertPrimitive.Overlay className={overlay} />
                <AlertPrimitive.Content className={panel}>
                    <AlertPrimitive.Title className="font-display text-2xl leading-tight">{title}</AlertPrimitive.Title>
                    <AlertPrimitive.Description asChild>
                        <div className="mt-2 text-[0.95rem] text-stone">{description}</div>
                    </AlertPrimitive.Description>
                    <div className="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                        <AlertPrimitive.Cancel asChild>
                            <Button variant="ghost">Keep it</Button>
                        </AlertPrimitive.Cancel>
                        <AlertPrimitive.Action asChild>
                            <Button variant={destructive ? 'destructive' : 'primary'} loading={processing} onClick={onConfirm}>
                                {confirmLabel}
                            </Button>
                        </AlertPrimitive.Action>
                    </div>
                </AlertPrimitive.Content>
            </AlertPrimitive.Portal>
        </AlertPrimitive.Root>
    );
}

export { ConfirmDialog, Dialog, DialogClose, DialogContent, DialogTrigger };
