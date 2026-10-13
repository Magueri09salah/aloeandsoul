import { Head, useForm } from '@inertiajs/react';
import { type FormEvent } from 'react';

import { Button } from '@/components/ui/button';
import { Field } from '@/components/ui/field';
import { Input } from '@/components/ui/input';

export default function ConfirmPassword() {
    const form = useForm({ password: '' });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        form.post('/admin/user/confirm-password', { onFinish: () => form.reset() });
    };

    return (
        <div className="rounded-lg border border-line bg-white p-8">
            <Head title="Confirm password — Admin" />
            <h1 className="font-display text-3xl">Confirm your password</h1>
            <p className="mt-2 text-sm text-stone">For your security, please confirm your password to continue.</p>
            <form onSubmit={submit} className="mt-6 space-y-5" noValidate>
                <Field label="Password" name="password" error={form.errors.password} required>
                    {(p) => <Input {...p} type="password" autoComplete="current-password" autoFocus value={form.data.password} onChange={(e) => form.setData('password', e.target.value)} />}
                </Field>
                <Button type="submit" className="w-full" loading={form.processing}>
                    Confirm
                </Button>
            </form>
        </div>
    );
}
