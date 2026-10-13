import * as React from 'react';

import { cn } from '@/lib/utils';

export const controlClasses =
    'w-full rounded-(--radius-control) border border-line bg-white px-3.5 text-[0.95rem] text-ink transition-colors duration-150 placeholder:text-stone/70 hover:border-stone/50 focus-visible:border-aloe focus-visible:outline-2 focus-visible:outline-offset-1 focus-visible:outline-aloe disabled:cursor-not-allowed disabled:bg-panel disabled:text-stone aria-invalid:border-clay aria-invalid:focus-visible:outline-clay';

function Input({ className, type = 'text', ...props }: React.ComponentProps<'input'>) {
    return <input type={type} data-slot="input" className={cn(controlClasses, 'h-11', className)} {...props} />;
}

function Textarea({ className, ...props }: React.ComponentProps<'textarea'>) {
    return <textarea data-slot="textarea" className={cn(controlClasses, 'min-h-24 py-2.5 leading-relaxed', className)} {...props} />;
}

/** Native select: the most reliable control on mobile and with screen readers. */
function Select({ className, children, ...props }: React.ComponentProps<'select'>) {
    return (
        <div className="relative">
            <select
                data-slot="select"
                className={cn(controlClasses, 'h-11 appearance-none pr-10', className)}
                {...props}
            >
                {children}
            </select>
            <svg
                className="pointer-events-none absolute top-1/2 right-3.5 size-4 -translate-y-1/2 text-stone"
                viewBox="0 0 16 16"
                fill="none"
                aria-hidden="true"
            >
                <path d="m4 6 4 4 4-4" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" />
            </svg>
        </div>
    );
}

function Label({ className, ...props }: React.ComponentProps<'label'>) {
    return <label data-slot="label" className={cn('text-sm font-medium text-ink', className)} {...props} />;
}

export { Input, Label, Select, Textarea };
