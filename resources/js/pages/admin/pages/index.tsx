import { Link } from '@inertiajs/react';

import { PageHeader, Panel, Table, td, th } from '@/components/admin/ui';
import { Badge } from '@/components/ui/badge';
import { useShared } from '@/hooks/use-shared';
import { formatDate } from '@/lib/date';

interface Row {
    id: number;
    title: string;
    slug: string;
    is_published: boolean;
    needs_review: boolean;
    updated_at: string;
    url: string;
}

export default function Pages({ pages }: { pages: Row[] }) {
    const { store } = useShared();

    return (
        <>
            <PageHeader
                title="Policy pages"
                description="Legal and support pages. Text in [square brackets] must be completed with your business details before launch."
            />
            <Panel padded={false}>
                <Table>
                    <thead>
                        <tr>
                            <th className={th}>Page</th>
                            <th className={th}>Status</th>
                            <th className={th}>Last edited</th>
                        </tr>
                    </thead>
                    <tbody>
                        {pages.map((p) => (
                            <tr key={p.id}>
                                <td className={td}>
                                    <Link href={`/admin/pages/${p.id}/edit`} className="font-medium hover:underline">
                                        {p.title}
                                    </Link>
                                    <span className="block text-xs text-stone">/{p.slug}</span>
                                </td>
                                <td className={td}>
                                    <span className="flex flex-wrap gap-1.5">
                                        {p.needs_review && <Badge tone="warning">Needs review</Badge>}
                                        {!p.is_published && <Badge tone="neutral">Hidden</Badge>}
                                        {p.is_published && !p.needs_review && <Badge tone="success">Live</Badge>}
                                    </span>
                                </td>
                                <td className={`${td} text-stone`}>{formatDate(p.updated_at, store.timezone)}</td>
                            </tr>
                        ))}
                    </tbody>
                </Table>
            </Panel>
        </>
    );
}
