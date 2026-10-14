import type { Collaborator } from '@/types';

import { ResponsiveImage } from '@/components/ui/responsive-image';
import { useI18n } from '@/lib/i18n';
import { cn } from '@/lib/utils';

function initials(name: string): string {
    return name
        .split(/\s+/)
        .filter(Boolean)
        .slice(0, 2)
        .map((w) => w[0]?.toUpperCase() ?? '')
        .join('');
}

/** Circular portrait or logo (echoing the circle of the Aloe & Soul mark). */
function Portrait({ c }: { c: Collaborator }) {
    const logo = c.image_style === 'logo';

    return (
        <div className={cn('mx-auto size-28 overflow-hidden rounded-full sm:size-32', logo ? 'border border-line bg-white p-4' : 'bg-sand')}>
            {c.image ? (
                <ResponsiveImage image={c.image} sizes="128px" alt="" className={logo ? 'object-contain' : 'object-cover'} />
            ) : (
                <span className="flex h-full w-full items-center justify-center font-display text-3xl text-dune-deep" aria-hidden="true">
                    {initials(c.name)}
                </span>
            )}
        </div>
    );
}

export function Collaborators({ heading, intro, items }: { heading: string; intro: string; items: Collaborator[] }) {
    const { t } = useI18n();
    if (items.length === 0) return null;

    return (
        <section aria-labelledby="collaborators-heading" className="container-page pt-20 lg:pt-28">
            <div className="mx-auto max-w-2xl text-center">
                <h2 id="collaborators-heading" className="font-display text-display-sm">
                    {heading}
                </h2>
                {intro && <p className="mt-3 text-lg text-stone">{intro}</p>}
            </div>
            <ul className="mt-12 flex flex-wrap justify-center gap-x-6 gap-y-10 sm:gap-x-10">
                {items.map((c) => {
                    const body = (
                        <>
                            <Portrait c={c} />
                            <p className="mt-4 font-medium group-hover:text-aloe">{c.name}</p>
                            {c.description && <p className="mt-1 text-sm text-stone">{c.description}</p>}
                        </>
                    );
                    return (
                        <li key={c.id} className="w-36 text-center sm:w-44">
                            {c.url ? (
                                <a href={c.url} target="_blank" rel="noopener noreferrer" className="group block">
                                    {body}
                                    <span className="sr-only"> {t('(opens their website in a new tab)')}</span>
                                </a>
                            ) : (
                                <div>{body}</div>
                            )}
                        </li>
                    );
                })}
            </ul>
        </section>
    );
}
