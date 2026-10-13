import type { Motif as MotifName } from '@/types';

import { cn } from '@/lib/utils';

/**
 * Line motifs that give each category a recognisable mark: a desert horizon,
 * the logo's circle, an Essaouira wave, an aloe leaf. Decorative only.
 */
export function Motif({ name, className }: { name: MotifName | string; className?: string }) {
    const common = {
        viewBox: '0 0 120 120',
        fill: 'none',
        stroke: 'currentColor',
        strokeWidth: 1.25,
        strokeLinecap: 'round' as const,
        'aria-hidden': true,
        className: cn('size-full', className),
    };

    switch (name) {
        case 'horizon':
            return (
                <svg {...common}>
                    <circle cx="60" cy="62" r="20" />
                    <path d="M8 82h104M20 92h80M34 102h52" />
                </svg>
            );
        case 'circle':
            return (
                <svg {...common}>
                    <circle cx="60" cy="60" r="44" />
                    <circle cx="60" cy="60" r="30" />
                    <circle cx="60" cy="60" r="16" />
                </svg>
            );
        case 'wave':
            return (
                <svg {...common}>
                    <path d="M10 48c12-10 22-10 34 0s22 10 34 0 22-10 32 0" />
                    <path d="M10 64c12-10 22-10 34 0s22 10 34 0 22-10 32 0" />
                    <path d="M10 80c12-10 22-10 34 0s22 10 34 0 22-10 32 0" />
                </svg>
            );
        default:
            return (
                <svg {...common}>
                    <path d="M62 12C48 34 38 58 40 82c1 12 7 21 16 26-3-14-3-32 0-50 2-16 4-32 6-46Z" />
                    <path d="M62 12c4 22 10 46 8 66-1 12-5 22-12 30" />
                </svg>
            );
    }
}
