import { router, useForm } from '@inertiajs/react';
import { type FormEvent } from 'react';

import { PageHeader, Panel } from '@/components/admin/ui';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Field } from '@/components/ui/field';
import { Input } from '@/components/ui/input';

interface ProfileProps {
    user: { name: string; email: string };
    twoFactor: { enabled: boolean; pending: boolean; qr_code: string | null; setup_key: string | null; recovery_codes: string[] | null };
    passwordConfirmed: boolean;
    status: string | null;
}

export default function Profile({ user, twoFactor, passwordConfirmed }: ProfileProps) {
    const details = useForm({ name: user.name, email: user.email });
    const password = useForm({ current_password: '', password: '', password_confirmation: '' });
    const confirm = useForm({ password: '' });
    const code = useForm({ code: '' });

    const saveDetails = (e: FormEvent) => {
        e.preventDefault();
        details.put('/admin/user/profile-information', { errorBag: 'updateProfileInformation', preserveScroll: true });
    };
    const savePassword = (e: FormEvent) => {
        e.preventDefault();
        password.put('/admin/user/password', { errorBag: 'updatePassword', preserveScroll: true, onSuccess: () => password.reset() });
    };
    const confirmPassword = (e: FormEvent) => {
        e.preventDefault();
        confirm.post('/admin/user/confirm-password', { preserveScroll: true, onFinish: () => confirm.reset() });
    };
    const confirmCode = (e: FormEvent) => {
        e.preventDefault();
        code.post('/admin/user/confirmed-two-factor-authentication', { errorBag: 'confirmTwoFactorAuthentication', preserveScroll: true });
    };

    return (
        <>
            <PageHeader title="Your account" />
            <div className="grid gap-6 lg:grid-cols-2">
                <Panel title="Details">
                    <form onSubmit={saveDetails} className="space-y-4" noValidate>
                        <Field label="Name" name="name" error={details.errors.name} required>
                            {(p) => <Input {...p} value={details.data.name} onChange={(e) => details.setData('name', e.target.value)} />}
                        </Field>
                        <Field label="Email" name="email" error={details.errors.email} required>
                            {(p) => <Input {...p} type="email" value={details.data.email} onChange={(e) => details.setData('email', e.target.value)} />}
                        </Field>
                        <Button type="submit" loading={details.processing}>
                            Save details
                        </Button>
                    </form>
                </Panel>

                <Panel title="Password">
                    <form onSubmit={savePassword} className="space-y-4" noValidate>
                        <Field label="Current password" name="current_password" error={password.errors.current_password} required>
                            {(p) => (
                                <Input {...p} type="password" autoComplete="current-password" value={password.data.current_password} onChange={(e) => password.setData('current_password', e.target.value)} />
                            )}
                        </Field>
                        <Field label="New password" name="password" error={password.errors.password} hint="At least 12 characters, with upper and lower case letters and a number." required>
                            {(p) => <Input {...p} type="password" autoComplete="new-password" value={password.data.password} onChange={(e) => password.setData('password', e.target.value)} />}
                        </Field>
                        <Field label="Confirm new password" name="password_confirmation" error={password.errors.password_confirmation} required>
                            {(p) => (
                                <Input
                                    {...p}
                                    type="password"
                                    autoComplete="new-password"
                                    value={password.data.password_confirmation}
                                    onChange={(e) => password.setData('password_confirmation', e.target.value)}
                                />
                            )}
                        </Field>
                        <Button type="submit" loading={password.processing}>
                            Change password
                        </Button>
                    </form>
                </Panel>

                <Panel
                    className="lg:col-span-2"
                    title="Two-factor authentication"
                    description="Ask for a code from an authenticator app (such as Google Authenticator or 1Password) when signing in."
                    actions={twoFactor.enabled ? <Badge tone="success">On</Badge> : <Badge tone="warning">Off</Badge>}
                >
                    {!passwordConfirmed ? (
                        <form onSubmit={confirmPassword} className="max-w-sm space-y-4" noValidate>
                            <p className="text-sm text-stone">Confirm your password to change two-factor settings.</p>
                            <Field label="Password" name="confirm_password" error={confirm.errors.password} required>
                                {(p) => <Input {...p} type="password" autoComplete="current-password" value={confirm.data.password} onChange={(e) => confirm.setData('password', e.target.value)} />}
                            </Field>
                            <Button type="submit" variant="secondary" loading={confirm.processing}>
                                Confirm password
                            </Button>
                        </form>
                    ) : twoFactor.pending ? (
                        <div className="grid gap-6 sm:grid-cols-[auto_1fr]">
                            {twoFactor.qr_code && <img src={twoFactor.qr_code} alt="QR code to add this account to your authenticator app" className="size-48 rounded border border-line bg-white p-2" />}
                            <div>
                                <p className="text-sm">Scan the QR code with your authenticator app, or enter this key:</p>
                                <p className="mt-2 font-mono text-sm break-all select-all">{twoFactor.setup_key}</p>
                                <form onSubmit={confirmCode} className="mt-5 max-w-xs space-y-3" noValidate>
                                    <Field label="6-digit code from the app" name="code" error={code.errors.code} required>
                                        {(p) => (
                                            <Input {...p} inputMode="numeric" autoComplete="one-time-code" maxLength={6} value={code.data.code} onChange={(e) => code.setData('code', e.target.value.replace(/\D/g, ''))} />
                                        )}
                                    </Field>
                                    <div className="flex gap-2">
                                        <Button type="submit" loading={code.processing}>
                                            Turn on
                                        </Button>
                                        <Button type="button" variant="ghost" onClick={() => router.delete('/admin/user/two-factor-authentication', { preserveScroll: true })}>
                                            Cancel
                                        </Button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    ) : twoFactor.enabled ? (
                        <div className="space-y-4">
                            {twoFactor.recovery_codes && (
                                <div className="rounded-md border border-dune/50 bg-sand p-4">
                                    <p className="text-sm font-medium">Save these recovery codes somewhere safe. Each can be used once if you lose your phone. They won’t be shown again.</p>
                                    <ul className="mt-3 grid grid-cols-2 gap-1 font-mono text-sm select-all sm:grid-cols-4">
                                        {twoFactor.recovery_codes.map((c) => (
                                            <li key={c}>{c}</li>
                                        ))}
                                    </ul>
                                </div>
                            )}
                            <div className="flex flex-wrap gap-2">
                                <Button variant="secondary" onClick={() => router.post('/admin/user/two-factor-recovery-codes', {}, { preserveScroll: true })}>
                                    New recovery codes
                                </Button>
                                <Button variant="destructive-outline" onClick={() => router.delete('/admin/user/two-factor-authentication', { preserveScroll: true })}>
                                    Turn off
                                </Button>
                            </div>
                        </div>
                    ) : (
                        <Button onClick={() => router.post('/admin/user/two-factor-authentication', {}, { preserveScroll: true })}>Set up two-factor authentication</Button>
                    )}
                </Panel>
            </div>
        </>
    );
}
