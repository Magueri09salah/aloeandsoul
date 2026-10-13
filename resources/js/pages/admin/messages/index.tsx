import { router } from '@inertiajs/react';

import type { Paginated } from '@/types';

import { EmptyState, PageHeader, Pagination, Panel } from '@/components/admin/ui';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { ConfirmDialog } from '@/components/ui/dialog';
import { Label } from '@/components/ui/input';
import { useShared } from '@/hooks/use-shared';
import { formatDateTime } from '@/lib/date';

interface Message {
    id: number;
    name: string;
    email: string;
    phone: string | null;
    message: string;
    read: boolean;
    created_at: string;
}

export default function Messages({ messages, unreadCount, filters }: { messages: Paginated<Message>; unreadCount: number; filters: { unread: boolean } }) {
    const { store } = useShared();

    return (
        <>
            <PageHeader title="Messages" description={`From the contact form. ${unreadCount} unread.`} />
            <div className="mb-4 flex items-center gap-2.5">
                <Checkbox id="unread" checked={filters.unread} onCheckedChange={(v) => router.get('/admin/messages', v === true ? { unread: 1 } : {}, { preserveState: true })} />
                <Label htmlFor="unread" className="font-normal">
                    Unread only
                </Label>
            </div>
            <Panel padded={false}>
                {messages.data.length === 0 ? (
                    <EmptyState title="No messages." />
                ) : (
                    <ul className="divide-y divide-line">
                        {messages.data.map((m) => (
                            <li key={m.id} className="px-5 py-4">
                                <div className="flex flex-wrap items-start justify-between gap-3">
                                    <div>
                                        <p className="font-medium">
                                            {m.name} {!m.read && <Badge tone="info">New</Badge>}
                                        </p>
                                        <p className="text-sm text-stone">
                                            <a href={`mailto:${m.email}`} className="underline underline-offset-4">
                                                {m.email}
                                            </a>
                                            {m.phone && `, ${m.phone}`}
                                            {' · '}
                                            {formatDateTime(m.created_at, store.timezone)}
                                        </p>
                                    </div>
                                    <div className="flex gap-1">
                                        {!m.read && (
                                            <Button size="sm" variant="ghost" onClick={() => router.post(`/admin/messages/${m.id}/read`, {}, { preserveScroll: true })}>
                                                Mark as read
                                            </Button>
                                        )}
                                        <ConfirmDialog
                                            trigger={
                                                <Button size="sm" variant="ghost" className="text-clay">
                                                    Delete
                                                </Button>
                                            }
                                            title="Delete this message?"
                                            description={`From ${m.name}.`}
                                            confirmLabel="Delete"
                                            onConfirm={() => router.delete(`/admin/messages/${m.id}`, { preserveScroll: true })}
                                        />
                                    </div>
                                </div>
                                <p className="mt-3 whitespace-pre-line">{m.message}</p>
                            </li>
                        ))}
                    </ul>
                )}
                <Pagination page={messages} />
            </Panel>
        </>
    );
}
