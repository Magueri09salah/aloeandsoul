import { SafeHtml } from '@/components/ui/safe-html';
import { formatDate } from '@/lib/date';

interface PageProps {
    page: { title: string; slug: string; body_html: string; updated_at: string | null };
}

export default function Page({ page }: PageProps) {
    return (
        <article className="container-page py-12 lg:py-20">
            <header className="max-w-3xl">
                <h1 className="font-display text-display-sm">{page.title}</h1>
                {page.updated_at && <p className="mt-3 text-sm text-stone">Last updated {formatDate(page.updated_at)}</p>}
            </header>
            <SafeHtml html={page.body_html} className="prose-editorial mt-10" />
        </article>
    );
}
