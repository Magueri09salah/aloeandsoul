import { router } from '@inertiajs/react';

import type { Paginated } from '@/types';

import { EmptyState, PageHeader, Pagination, Panel, Table, td, th } from '@/components/admin/ui';
import { Select } from '@/components/ui/input';
import { useShared } from '@/hooks/use-shared';
import { formatDateTime } from '@/lib/date';

interface Entry {
    id: number;
    action: string;
    user: string | null;
    subject: string | null;
    properties: Record<string, unknown> | null;
    ip_address: string | null;
    created_at: string;
}

export default function AuditLog({ entries, filters, actions }: { entries: Paginated<Entry>; filters: { action: string | null }; actions: string[] }) {
    const { store } = useShared();

    return (
        <>
            <PageHeader title="Activity log" description="Who changed what in the admin. Kept for two years." />
            <Panel padded={false}>
                <div className="border-b border-line p-4">
                    <label htmlFor="action" className="sr-only">
                        Filter by action
                    </label>
                    <Select
                        id="action"
                        value={filters.action ?? ''}
                        onChange={(e) => router.get('/admin/audit-log', e.target.value ? { action: e.target.value } : {}, { preserveState: true })}
                        className="h-10 max-w-xs"
                    >
                        <option value="">All actions</option>
                        {actions.map((a) => (
                            <option key={a} value={a}>
                                {a}
                            </option>
                        ))}
                    </Select>
                </div>
                {entries.data.length === 0 ? (
                    <EmptyState title="Nothing recorded yet." />
                ) : (
                    <Table>
                        <thead>
                            <tr>
                                <th className={th}>When</th>
                                <th className={th}>Who</th>
                                <th className={th}>Action</th>
                                <th className={th}>Details</th>
                            </tr>
                        </thead>
                        <tbody>
                            {entries.data.map((e) => (
                                <tr key={e.id} className="align-top">
                                    <td className={`${td} whitespace-nowrap text-stone`}>{formatDateTime(e.created_at, store.timezone)}</td>
                                    <td className={td}>
                                        {e.user ?? 'System'}
                                        {e.ip_address && <span className="block text-xs text-stone">{e.ip_address}</span>}
                                    </td>
                                    <td className={td}>
                                        <code className="text-xs">{e.action}</code>
                                        {e.subject && <span className="block text-xs text-stone">{e.subject}</span>}
                                    </td>
                                    <td className={td}>
                                        {e.properties && (
                                            <details>
                                                <summary className="cursor-pointer text-xs text-stone">Show</summary>
                                                <pre className="mt-2 max-w-md overflow-x-auto rounded bg-panel p-2 text-xs">{JSON.stringify(e.properties, null, 2)}</pre>
                                            </details>
                                        )}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </Table>
                )}
                <Pagination page={entries} />
            </Panel>
        </>
    );
}
