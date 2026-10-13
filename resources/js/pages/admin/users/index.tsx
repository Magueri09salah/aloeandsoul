import { useForm } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import { useState, type FormEvent } from 'react';

import { PageHeader, Panel, Table, td, th } from '@/components/admin/ui';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Switch } from '@/components/ui/checkbox';
import { Dialog, DialogContent } from '@/components/ui/dialog';
import { Field } from '@/components/ui/field';
import { Input, Label, Select } from '@/components/ui/input';
import { useShared } from '@/hooks/use-shared';
import { formatDateTime } from '@/lib/date';

interface User {
    id: number;
    name: string;
    email: string;
    role: string;
    role_label: string;
    is_active: boolean;
    two_factor: boolean;
    last_login_at: string | null;
    is_me: boolean;
}

interface Role {
    value: string;
    label: string;
    description: string;
}

export default function Users({ users, roles }: { users: User[]; roles: Role[] }) {
    const { store } = useShared();
    const [creating, setCreating] = useState(false);
    const [editing, setEditing] = useState<User | null>(null);

    return (
        <>
            <PageHeader
                title="Team"
                description="People who can sign in to this admin. Accounts are switched off rather than deleted, so the activity log keeps its history."
                actions={
                    <Button onClick={() => setCreating(true)}>
                        <Plus aria-hidden="true" /> Add person
                    </Button>
                }
            />
            <Panel padded={false}>
                <Table>
                    <thead>
                        <tr>
                            <th className={th}>Name</th>
                            <th className={th}>Role</th>
                            <th className={th}>Security</th>
                            <th className={th}>Last sign-in</th>
                            <th className={th}>
                                <span className="sr-only">Actions</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        {users.map((u) => (
                            <tr key={u.id}>
                                <td className={td}>
                                    <span className="font-medium">{u.name}</span>
                                    {u.is_me && <span className="text-stone"> (you)</span>}
                                    <span className="block text-xs text-stone">{u.email}</span>
                                </td>
                                <td className={td}>
                                    {u.role_label} {!u.is_active && <Badge tone="neutral">Off</Badge>}
                                </td>
                                <td className={td}>{u.two_factor ? <Badge tone="success">Two-factor on</Badge> : <Badge tone="warning">Password only</Badge>}</td>
                                <td className={`${td} text-stone`}>{formatDateTime(u.last_login_at, store.timezone)}</td>
                                <td className={`${td} text-right`}>
                                    <Button size="sm" variant="ghost" onClick={() => setEditing(u)}>
                                        Edit
                                    </Button>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </Table>
            </Panel>

            <Panel title="What each role can do" className="mt-6">
                <dl className="grid gap-4 sm:grid-cols-3">
                    {roles.map((r) => (
                        <div key={r.value}>
                            <dt className="font-medium">{r.label}</dt>
                            <dd className="mt-1 text-sm text-stone">{r.description}</dd>
                        </div>
                    ))}
                </dl>
            </Panel>

            <Dialog open={creating} onOpenChange={setCreating}>
                {creating && (
                    <DialogContent title="Add a person" description="They sign in with this email and password, then should change the password and turn on two-factor authentication.">
                        <CreateForm roles={roles} onDone={() => setCreating(false)} />
                    </DialogContent>
                )}
            </Dialog>
            <Dialog open={editing !== null} onOpenChange={(o) => !o && setEditing(null)}>
                {editing && (
                    <DialogContent title={`Edit ${editing.name}`}>
                        <EditForm user={editing} roles={roles} onDone={() => setEditing(null)} />
                    </DialogContent>
                )}
            </Dialog>
        </>
    );
}

function CreateForm({ roles, onDone }: { roles: Role[]; onDone: () => void }) {
    const form = useForm({ name: '', email: '', role: 'staff', password: '', password_confirmation: '' });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        form.post('/admin/users', { preserveScroll: true, onSuccess: onDone, onFinish: () => form.reset('password', 'password_confirmation') });
    };

    return (
        <form onSubmit={submit} noValidate className="space-y-4">
            <Field label="Name" name="name" error={form.errors.name} required>
                {(p) => <Input {...p} value={form.data.name} onChange={(e) => form.setData('name', e.target.value)} />}
            </Field>
            <Field label="Email" name="email" error={form.errors.email} required>
                {(p) => <Input {...p} type="email" value={form.data.email} onChange={(e) => form.setData('email', e.target.value)} autoComplete="off" />}
            </Field>
            <Field label="Role" name="role" error={form.errors.role}>
                {(p) => (
                    <Select {...p} value={form.data.role} onChange={(e) => form.setData('role', e.target.value)}>
                        {roles.map((r) => (
                            <option key={r.value} value={r.value}>
                                {r.label}
                            </option>
                        ))}
                    </Select>
                )}
            </Field>
            <Field label="Temporary password" name="password" error={form.errors.password} hint="At least 12 characters, with upper and lower case letters and a number." required>
                {(p) => <Input {...p} type="password" autoComplete="new-password" value={form.data.password} onChange={(e) => form.setData('password', e.target.value)} />}
            </Field>
            <Field label="Confirm password" name="password_confirmation" error={form.errors.password_confirmation} required>
                {(p) => <Input {...p} type="password" autoComplete="new-password" value={form.data.password_confirmation} onChange={(e) => form.setData('password_confirmation', e.target.value)} />}
            </Field>
            <div className="flex justify-end">
                <Button type="submit" loading={form.processing}>
                    Create account
                </Button>
            </div>
        </form>
    );
}

function EditForm({ user, roles, onDone }: { user: User; roles: Role[]; onDone: () => void }) {
    const form = useForm({ role: user.role, is_active: user.is_active });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        form.put(`/admin/users/${user.id}`, { preserveScroll: true, onSuccess: onDone });
    };

    return (
        <form onSubmit={submit} noValidate className="space-y-4">
            <Field label="Role" name="role" error={form.errors.role}>
                {(p) => (
                    <Select {...p} value={form.data.role} onChange={(e) => form.setData('role', e.target.value)}>
                        {roles.map((r) => (
                            <option key={r.value} value={r.value}>
                                {r.label}
                            </option>
                        ))}
                    </Select>
                )}
            </Field>
            <div className="flex items-center gap-3">
                <Switch id="active" checked={form.data.is_active} onCheckedChange={(v) => form.setData('is_active', v)} />
                <Label htmlFor="active" className="font-normal">
                    Can sign in
                </Label>
            </div>
            {!form.data.is_active && <p className="text-sm text-stone">Switching this off signs them out everywhere immediately.</p>}
            <div className="flex justify-end">
                <Button type="submit" loading={form.processing}>
                    Save
                </Button>
            </div>
        </form>
    );
}
