import type { Paginated } from '@/types';

import { EmptyState, PageHeader, Pagination, Panel, Table, td, th } from '@/components/admin/ui';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { useShared } from '@/hooks/use-shared';
import { formatDate } from '@/lib/date';

interface Row {
    id: number;
    email: string;
    consented_at: string;
    unsubscribed: boolean;
}

export default function Subscribers({ subscribers, activeCount }: { subscribers: Paginated<Row>; activeCount: number }) {
    const { store } = useShared();

    return (
        <>
            <PageHeader
                title="Newsletter subscribers"
                description={`${activeCount} people agreed to receive emails. The export includes the date and wording of their consent.`}
                actions={
                    activeCount > 0 && (
                        <Button asChild variant="secondary">
                            <a href="/admin/subscribers/export">Download CSV</a>
                        </Button>
                    )
                }
            />
            <Panel padded={false}>
                {subscribers.data.length === 0 ? (
                    <EmptyState title="No subscribers yet." />
                ) : (
                    <Table>
                        <thead>
                            <tr>
                                <th className={th}>Email</th>
                                <th className={th}>Subscribed</th>
                                <th className={th}>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            {subscribers.data.map((s) => (
                                <tr key={s.id}>
                                    <td className={td}>{s.email}</td>
                                    <td className={`${td} text-stone`}>{formatDate(s.consented_at, store.timezone)}</td>
                                    <td className={td}>{s.unsubscribed ? <Badge>Unsubscribed</Badge> : <Badge tone="success">Subscribed</Badge>}</td>
                                </tr>
                            ))}
                        </tbody>
                    </Table>
                )}
                <Pagination page={subscribers} />
            </Panel>
        </>
    );
}
