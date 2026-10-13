import { Checkbox as CheckboxPrimitive, Switch as SwitchPrimitive } from 'radix-ui';
import * as React from 'react';

import { cn } from '@/lib/utils';

function Checkbox({ className, ...props }: React.ComponentProps<typeof CheckboxPrimitive.Root>) {
    return (
        <CheckboxPrimitive.Root
            data-slot="checkbox"
            className={cn(
                'peer size-5 shrink-0 rounded-[4px] border border-stone/60 bg-white transition-colors focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-aloe disabled:opacity-50 aria-invalid:border-clay data-[state=checked]:border-ink data-[state=checked]:bg-ink data-[state=checked]:text-ivory',
                className,
            )}
            {...props}
        >
            <CheckboxPrimitive.Indicator className="flex items-center justify-center">
                <svg viewBox="0 0 16 16" className="size-3.5" fill="none" aria-hidden="true">
                    <path d="m3.5 8.5 3 3 6-7" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" />
                </svg>
            </CheckboxPrimitive.Indicator>
        </CheckboxPrimitive.Root>
    );
}

function Switch({ className, ...props }: React.ComponentProps<typeof SwitchPrimitive.Root>) {
    return (
        <SwitchPrimitive.Root
            data-slot="switch"
            className={cn(
                'inline-flex h-6 w-11 shrink-0 items-center rounded-full border border-transparent bg-line transition-colors focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-aloe disabled:opacity-50 data-[state=checked]:bg-aloe',
                className,
            )}
            {...props}
        >
            <SwitchPrimitive.Thumb className="block size-5 translate-x-0.5 rounded-full bg-white shadow-sm transition-transform data-[state=checked]:translate-x-[1.375rem]" />
        </SwitchPrimitive.Root>
    );
}

export { Checkbox, Switch };
