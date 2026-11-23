import { useId } from 'react';

import type { Motif as MotifName } from '@/types';

import { cn } from '@/lib/utils';

/** One wave crest and trough (68 units), repeated past both edges so the line can slide one period and loop. */
const LONG_WAVE = (y: number) => `M-58 ${y}c12-10 22-10 34 0${'s22 10 34 0s22-10 34 0'.repeat(3)}`;

/**
 * Line motifs that give each category a recognisable mark: a desert horizon,
 * the logo's circle, an Essaouira wave, an aloe leaf. Decorative only.
 *
 * `animated` sets them in slow motion (the sun rises, the circles ripple, the
 * waves flow, the leaf sways); the movement is defined in app.css and only
 * runs when the visitor has not asked for reduced motion.
 */
export function Motif({ name, className, animated = false }: { name: MotifName | string; className?: string; animated?: boolean }) {
    // Ids for the clip path, gradient and mask; kept to characters that are safe inside url(#…).
    const id = `motif${useId().replace(/[^\w-]/g, '')}`;
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
                    {animated && (
                        <clipPath id={`${id}-sky`}>
                            <rect width="120" height="82.5" />
                        </clipPath>
                    )}
                    {/* Above the horizon only, so the sun rises from behind it. */}
                    <g clipPath={animated ? `url(#${id}-sky)` : undefined}>
                        <circle cx="60" cy="62" r="20" className={animated ? 'motif-sun' : undefined} />
                    </g>
                    <path d="M8 82h104M20 92h80M34 102h52" />
                </svg>
            );
        case 'circle':
            return (
                <svg {...common}>
                    {[44, 30, 16].map((r, i) => (
                        <circle
                            key={r}
                            cx="60"
                            cy="60"
                            r={r}
                            className={animated ? 'motif-ring' : undefined}
                            style={animated ? { animationDelay: `${i * 0.35}s` } : undefined}
                        />
                    ))}
                </svg>
            );
        case 'wave':
            if (!animated) {
                return (
                    <svg {...common}>
                        <path d="M10 48c12-10 22-10 34 0s22 10 34 0 22-10 32 0" />
                        <path d="M10 64c12-10 22-10 34 0s22 10 34 0 22-10 32 0" />
                        <path d="M10 80c12-10 22-10 34 0s22 10 34 0 22-10 32 0" />
                    </svg>
                );
            }
            return (
                <svg {...common}>
                    {/* The lines fade out at both ends, like the static motif's length. */}
                    <linearGradient id={`${id}-fade`} x1="10" x2="110" gradientUnits="userSpaceOnUse">
                        <stop offset="0" stopColor="#fff" stopOpacity="0" />
                        <stop offset="0.18" stopColor="#fff" />
                        <stop offset="0.82" stopColor="#fff" />
                        <stop offset="1" stopColor="#fff" stopOpacity="0" />
                    </linearGradient>
                    <mask id={`${id}-mask`} maskUnits="userSpaceOnUse" x="0" y="0" width="120" height="120">
                        <rect x="10" y="0" width="100" height="120" fill={`url(#${id}-fade)`} stroke="none" />
                    </mask>
                    <g mask={`url(#${id}-mask)`}>
                        {[48, 64, 80].map((y) => (
                            <path key={y} d={LONG_WAVE(y)} className="motif-wave" />
                        ))}
                    </g>
                </svg>
            );
        default:
            return (
                <svg {...common}>
                    <g className={animated ? 'motif-leaf' : undefined}>
                        <path d="M62 12C48 34 38 58 40 82c1 12 7 21 16 26-3-14-3-32 0-50 2-16 4-32 6-46Z" />
                        <path d="M62 12c4 22 10 46 8 66-1 12-5 22-12 30" />
                    </g>
                </svg>
            );
    }
}
