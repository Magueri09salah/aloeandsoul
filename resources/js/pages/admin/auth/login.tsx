import { Head, Link, useForm } from '@inertiajs/react';
import { type FormEvent } from 'react';

import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Field } from '@/components/ui/field';
import { Input, Label } from '@/components/ui/input';

export default function Login({ status }: { status?: string | null }) {
    const form = useForm({ email: '', password: '', remember: false });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        form.post('/admin/login', { onFinish: () => form.reset('password') });
    };

    return (
        <div className="rounded-lg border border-line bg-white p-8">
            <Head title="Sign in — Admin" />
            <h1 className="font-display text-3xl">Sign in</h1>
            <p className="mt-2 text-sm text-stone">Shop administration</p>
            {status && (
                <p className="mt-4 rounded-md bg-[#DDE7DA] px-3 py-2 text-sm text-[#2E4A31]" role="status">
                    {status}
                </p>
            )}
            <form onSubmit={submit} className="mt-6 space-y-5" noValidate>
                <Field label="Email" name="email" error={form.errors.email} required>
                    {(p) => <Input {...p} type="email" autoComplete="username" autoFocus value={form.data.email} onChange={(e) => form.setData('email', e.target.value)} />}
                </Field>
                <Field label="Password" name="password" error={form.errors.password} required>
                    {(p) => <Input {...p} type="password" autoComplete="current-password" value={form.data.password} onChange={(e) => form.setData('password', e.target.value)} />}
                </Field>
                <div className="flex items-center justify-between gap-3">
                    <div className="flex items-center gap-2.5">
                        <Checkbox id="remember" checked={form.data.remember} onCheckedChange={(v) => form.setData('remember', v === true)} />
                        <Label htmlFor="remember" className="font-normal">
                            Keep me signed in
                        </Label>
                    </div>
                    <Link href="/admin/forgot-password" className="text-sm underline underline-offset-4 hover:text-aloe">
                        Forgot password?
                    </Link>
                </div>
                <Button type="submit" className="w-full" loading={form.processing}>
                    Sign in
                </Button>
            </form>
        </div>
    );
}
