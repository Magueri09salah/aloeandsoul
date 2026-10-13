import { useState } from 'react';

import { formatMoney } from '@/lib/money';

interface Point {
    date: string;
    orders: number;
    value_minor: number;
}

/**
 * Daily order value as columns: one series, one hue, rounded data ends on a
 * shared baseline, a 2px gap between bars, hover/focus tooltip, and a table
 * for screen readers.
 */
export function DailyChart({ series, currency }: { series: Point[]; currency: string }) {
    const [active, setActive] = useState<number | null>(null);
    const max = Math.max(1, ...series.map((p) => p.value_minor));
    const height = 160;
    const n = series.length;
    const gap = 2;
    const width = 720;
    const barWidth = Math.max(2, (width - gap * (n - 1)) / n);
    const labelEvery = n > 31 ? 14 : n > 8 ? 7 : 1;
    const short = new Intl.DateTimeFormat('en-GB', { day: 'numeric', month: 'short', timeZone: 'UTC' });
    const fmt = (d: string) => short.format(new Date(`${d}T00:00:00Z`));
    const current = active !== null ? series[active] : undefined;

    return (
        <figure>
            <div className="relative">
                <svg viewBox={`0 0 ${width} ${height + 24}`} className="h-48 w-full" role="img" aria-label="Order value per day" preserveAspectRatio="none">
                    <line x1="0" x2={width} y1={height} y2={height} stroke="var(--color-line)" strokeWidth="1" vectorEffect="non-scaling-stroke" />
                    {series.map((p, i) => {
                        const h = p.value_minor === 0 ? 0 : Math.max(3, (p.value_minor / max) * (height - 8));
                        const x = i * (barWidth + gap);
                        return (
                            <g key={p.date}>
                                {/* Hit target taller and wider than the mark. */}
                                <rect
                                    x={x}
                                    y={0}
                                    width={barWidth + gap}
                                    height={height}
                                    fill="transparent"
                                    tabIndex={0}
                                    onMouseEnter={() => setActive(i)}
                                    onMouseLeave={() => setActive(null)}
                                    onFocus={() => setActive(i)}
                                    onBlur={() => setActive(null)}
                                    aria-label={`${fmt(p.date)}: ${p.orders} orders, ${formatMoney(p.value_minor, currency)}`}
                                />
                                {h > 0 && (
                                    <path
                                        d={roundedTop(x, height - h, barWidth, h, Math.min(4, barWidth / 2))}
                                        fill={active === i ? 'var(--color-ink)' : 'var(--color-aloe)'}
                                        pointerEvents="none"
                                    />
                                )}
                                {i % labelEvery === 0 && (
                                    <text x={x + barWidth / 2} y={height + 17} textAnchor="middle" className="fill-stone" fontSize="11">
                                        {fmt(p.date)}
                                    </text>
                                )}
                            </g>
                        );
                    })}
                </svg>
                {current && active !== null && (
                    <div
                        className="pointer-events-none absolute top-0 rounded-md bg-ink px-3 py-2 text-xs text-ivory shadow-overlay"
                        style={{ left: `${Math.min(80, Math.max(0, (active / n) * 100))}%` }}
                        role="status"
                    >
                        <p className="font-medium">{fmt(current.date)}</p>
                        <p>
                            {current.orders} {current.orders === 1 ? 'order' : 'orders'}, {formatMoney(current.value_minor, currency)}
                        </p>
                    </div>
                )}
            </div>
            <figcaption className="sr-only">
                <table>
                    <caption>Order value per day</caption>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Orders</th>
                            <th>Value</th>
                        </tr>
                    </thead>
                    <tbody>
                        {series.map((p) => (
                            <tr key={p.date}>
                                <td>{p.date}</td>
                                <td>{p.orders}</td>
                                <td>{formatMoney(p.value_minor, currency)}</td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </figcaption>
        </figure>
    );
}

/** Bar with rounded top corners only, anchored square to the baseline. */
function roundedTop(x: number, y: number, w: number, h: number, r: number): string {
    const rr = Math.min(r, h);
    return `M${x},${y + h} L${x},${y + rr} Q${x},${y} ${x + rr},${y} L${x + w - rr},${y} Q${x + w},${y} ${x + w},${y + rr} L${x + w},${y + h} Z`;
}
