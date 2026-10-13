import { Link } from '@inertiajs/react';

import { Button } from '@/components/ui/button';

export default function ProductUnavailable({ name, category }: { name: string; category: { name: string; slug: string } }) {
    return (
        <section className="container-page max-w-2xl py-24 text-center">
            <h1 className="font-display text-display-sm">{name} is no longer available</h1>
            <p className="mt-4 text-lg text-stone">This product has been discontinued. You may find something similar in the same collection.</p>
            <div className="mt-8 flex flex-wrap justify-center gap-3">
                <Button asChild>
                    <Link href={`/shop/${category.slug}`}>Browse {category.name}</Link>
                </Button>
                <Button asChild variant="secondary">
                    <Link href="/contact">Ask us</Link>
                </Button>
            </div>
        </section>
    );
}
