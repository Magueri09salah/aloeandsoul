import { Head, useForm } from '@inertiajs/react';
import { useState, type FormEvent } from 'react';

import { Button } from '@/components/ui/button';
import { Field } from '@/components/ui/field';
import { Input } from '@/components/ui/input';

export default function TwoFactorChallenge() {
    const [useRecovery, setUseRecovery] = useState(false);
    const form = useForm({ code: '', recovery_code: '' });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        form.transform((d) => (useRecovery ? { recovery_code: d.recovery_code } : { code: d.code }));
        form.post('/admin/two-factor-challenge', { onFinish: () => form.reset() });
    };

    return (
        <div className="rounded-lg border border-line bg-white p-8">
            <Head title="Two-factor authentication — Admin" />
            <h1 className="font-display text-3xl">Confirm it’s you</h1>
            <p className="mt-2 text-sm text-stone">
                {useRecovery ? 'Enter one of your recovery codes.' : 'Enter the 6-digit code from your authenticator app.'}
            </p>
            <form onSubmit={submit} className="mt-6 space-y-5" noValidate>
                {useRecovery ? (
                    <Field label="Recovery code" name="recovery_code" error={form.errors.recovery_code} required>
                        {(p) => <Input {...p} autoComplete="one-time-code" autoFocus value={form.data.recovery_code} onChange={(e) => form.setData('recovery_code', e.target.value)} />}
                    </Field>
                ) : (
                    <Field label="Authentication code" name="code" error={form.errors.code} required>
                        {(p) => (
                            <Input
                                {...p}
                                inputMode="numeric"
                                autoComplete="one-time-code"
                                autoFocus
                                maxLength={6}
                                value={form.data.code}
                                onChange={(e) => form.setData('code', e.target.value.replace(/\D/g, ''))}
                            />
                        )}
                    </Field>
                )}
                <Button type="submit" className="w-full" loading={form.processing}>
                    Continue
                </Button>
            </form>
            <button type="button" onClick={() => setUseRecovery(!useRecovery)} className="mt-6 text-sm underline underline-offset-4">
                {useRecovery ? 'Use an authentication code' : 'Use a recovery code instead'}
            </button>
        </div>
    );
}
