import { cva, type VariantProps } from 'class-variance-authority';
import { Slot } from 'radix-ui';
import * as React from 'react';

import { Spinner } from '@/components/ui/spinner';
import { cn } from '@/lib/utils';

const buttonVariants = cva(
    'inline-flex shrink-0 items-center justify-center gap-2 rounded-full font-sans [font-weight:var(--button-weight,500)] [letter-spacing:var(--button-tracking,normal)] whitespace-nowrap [text-transform:var(--button-case,none)] transition-[background-color,color,border-color,transform] duration-150 ease-(--ease-out-soft) select-none active:scale-[0.98] disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg]:shrink-0',
    {
        variants: {
            variant: {
                primary: 'bg-primary text-primary-foreground hover:bg-primary-hover',
                secondary: 'border border-ink bg-transparent text-ink hover:bg-ink hover:text-ivory',
                quiet: 'bg-sand text-ink hover:bg-line',
                ghost: 'text-ink hover:bg-sand',
                link: 'rounded-none px-0 text-ink underline decoration-1 underline-offset-4 hover:text-aloe',
                destructive: 'bg-clay text-white hover:bg-clay/90',
                'destructive-outline': 'border border-clay text-clay hover:bg-clay-soft',
            },
            size: {
                sm: 'h-9 px-4 text-sm [&_svg]:size-4',
                md: 'h-11 px-6 text-[0.95rem] [&_svg]:size-4',
                lg: 'h-13 px-8 text-base [&_svg]:size-5',
                icon: 'size-11 [&_svg]:size-5',
                'icon-sm': 'size-9 [&_svg]:size-4',
            },
            /**
             * Lets a long label (French, set in capitals on the storefront) break
             * onto a second line instead of running out of a narrow box. The
             * button keeps its usual height while the label fits on one line.
             */
            wrap: {
                true: 'h-auto max-w-full text-center leading-tight text-balance whitespace-normal',
            },
        },
        compoundVariants: [
            { variant: 'link', className: 'h-auto px-0' },
            { wrap: true, size: 'sm', className: 'min-h-9 py-2' },
            { wrap: true, size: 'md', className: 'min-h-11 py-2.5' },
            { wrap: true, size: 'lg', className: 'min-h-13 py-3' },
        ],
        defaultVariants: { variant: 'primary', size: 'md' },
    },
);

export interface ButtonProps extends React.ComponentProps<'button'>, VariantProps<typeof buttonVariants> {
    asChild?: boolean;
    loading?: boolean;
}

function Button({
    className,
    variant,
    size,
    wrap,
    asChild = false,
    loading = false,
    disabled,
    children,
    ...props
}: ButtonProps) {
    const Comp = asChild ? Slot.Root : 'button';

    return (
        <Comp
            data-slot="button"
            className={cn(buttonVariants({ variant, size, wrap, className }))}
            disabled={asChild ? undefined : disabled || loading}
            aria-busy={loading || undefined}
            {...props}
        >
            {asChild ? (
                children
            ) : (
                <>
                    {loading && <Spinner />}
                    {children}
                </>
            )}
        </Comp>
    );
}

export { Button, buttonVariants };
