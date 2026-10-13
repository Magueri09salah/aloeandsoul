import { Head, useForm } from '@inertiajs/react';
import { type FormEvent } from 'react';

import { Button } from '@/components/ui/button';
import { Field } from '@/components/ui/field';
import { Input } from '@/components/ui/input';

export default function ResetPassword({ email, token }: { email: string; token: string }) {
    const form = useForm({ token, email, password: '', password_confirmation: '' });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        form.post('/admin/reset-password', { onFinish: () => form.reset('password', 'password_confirmation') });
    };

    return (
        <div className="rounded-lg border border-line bg-white p-8">
            <Head title="Choose a new password — Admin" />
            <h1 className="font-display text-3xl">Choose a new password</h1>
            <form onSubmit={submit} className="mt-6 space-y-5" noValidate>
                <Field label="Email" name="email" error={form.errors.email} required>
                    {(p) => <Input {...p} type="email" autoComplete="username" value={form.data.email} onChange={(e) => form.setData('email', e.target.value)} />}
                </Field>
                <Field label="New password" name="password" error={form.errors.password} hint="At least 12 characters, with upper and lower case letters and a number." required>
                    {(p) => <Input {...p} type="password" autoComplete="new-password" value={form.data.password} onChange={(e) => form.setData('password', e.target.value)} />}
                </Field>
                <Field label="Confirm new password" name="password_confirmation" error={form.errors.password_confirmation} required>
                    {(p) => (
                        <Input {...p} type="password" autoComplete="new-password" value={form.data.password_confirmation} onChange={(e) => form.setData('password_confirmation', e.target.value)} />
                    )}
                </Field>
                <Button type="submit" className="w-full" loading={form.processing}>
                    Save new password
                </Button>
            </form>
        </div>
    );
}
