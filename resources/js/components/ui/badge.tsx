import { cva, type VariantProps } from 'class-variance-authority';
import * as React from 'react';

import { cn } from '@/lib/utils';

const badgeVariants = cva('inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-medium whitespace-nowrap', {
    variants: {
        tone: {
            neutral: 'bg-sand text-ink',
            success: 'bg-[#DDE7DA] text-[#2E4A31]',
            warning: 'bg-[#F3E6C8] text-[#6B4E12]',
            danger: 'bg-clay-soft text-clay',
            info: 'bg-[#DCE5EA] text-[#2F4A5A]',
            dark: 'bg-ink text-ivory',
            outline: 'border border-line bg-white text-stone',
        },
    },
    defaultVariants: { tone: 'neutral' },
});

function Badge({ className, tone, ...props }: React.ComponentProps<'span'> & VariantProps<typeof badgeVariants>) {
    return <span className={cn(badgeVariants({ tone }), className)} {...props} />;
}

export { Badge, badgeVariants };
