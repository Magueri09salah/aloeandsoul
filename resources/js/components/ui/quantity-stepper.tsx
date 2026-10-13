import { Minus, Plus } from 'lucide-react';

import { cn } from '@/lib/utils';

interface QuantityStepperProps {
    value: number;
    min?: number;
    max: number;
    onChange: (value: number) => void;
    label: string;
    disabled?: boolean;
    size?: 'sm' | 'md';
    className?: string;
}

/** Pill-shaped −/+ control with 44px targets. Announces the current value. */
export function QuantityStepper({ value, min = 1, max, onChange, label, disabled, size = 'md', className }: QuantityStepperProps) {
    const button =
        'inline-flex items-center justify-center rounded-full text-ink transition-colors hover:bg-sand disabled:cursor-not-allowed disabled:opacity-35 disabled:hover:bg-transparent';
    const dims = size === 'sm' ? 'size-9' : 'size-11';

    return (
        <div
            role="group"
            aria-label={label}
            className={cn('inline-flex items-center rounded-full border border-line bg-white', className)}
        >
            <button
                type="button"
                className={cn(button, dims)}
                onClick={() => onChange(Math.max(min, value - 1))}
                disabled={disabled || value <= min}
                aria-label={`Decrease ${label.toLowerCase()}`}
            >
                <Minus className="size-4" aria-hidden="true" />
            </button>
            <output aria-live="polite" className={cn('min-w-8 text-center tabular-nums', size === 'sm' ? 'text-sm' : 'text-base')}>
                {value}
            </output>
            <button
                type="button"
                className={cn(button, dims)}
                onClick={() => onChange(Math.min(max, value + 1))}
                disabled={disabled || value >= max}
                aria-label={`Increase ${label.toLowerCase()}`}
            >
                <Plus className="size-4" aria-hidden="true" />
            </button>
        </div>
    );
}
