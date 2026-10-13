import { Head, Link, useForm } from '@inertiajs/react';
import { type FormEvent } from 'react';

import { Button } from '@/components/ui/button';
import { Field } from '@/components/ui/field';
import { Input } from '@/components/ui/input';

export default function ForgotPassword({ status }: { status?: string | null }) {
    const form = useForm({ email: '' });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        form.post('/admin/forgot-password');
    };

    return (
        <div className="rounded-lg border border-line bg-white p-8">
            <Head title="Reset password — Admin" />
            <h1 className="font-display text-3xl">Reset your password</h1>
            <p className="mt-2 text-sm text-stone">Enter your email address. If it belongs to an admin account, we’ll send a reset link.</p>
            {status && (
                <p className="mt-4 rounded-md bg-[#DDE7DA] px-3 py-2 text-sm text-[#2E4A31]" role="status">
                    {status}
                </p>
            )}
            <form onSubmit={submit} className="mt-6 space-y-5" noValidate>
                <Field label="Email" name="email" error={form.errors.email} required>
                    {(p) => <Input {...p} type="email" autoComplete="username" autoFocus value={form.data.email} onChange={(e) => form.setData('email', e.target.value)} />}
                </Field>
                <Button type="submit" className="w-full" loading={form.processing}>
                    Send reset link
                </Button>
            </form>
            <Link href="/admin/login" className="mt-6 inline-block text-sm underline underline-offset-4">
                Back to sign in
            </Link>
        </div>
    );
}
