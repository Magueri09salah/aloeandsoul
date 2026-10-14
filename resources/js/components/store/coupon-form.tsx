import { router, useForm } from '@inertiajs/react';
import { type FormEvent } from 'react';

import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { useI18n } from '@/lib/i18n';

/** Apply or remove a discount code. The discount itself is always computed on the server. */
export function CouponForm({ code, error }: { code: string | null; error?: string | null }) {
    const form = useForm({ code: '' });
    const { t, to } = useI18n();

    const submit = (e: FormEvent) => {
        e.preventDefault();
        form.post(to('/cart/coupon'), { preserveScroll: true, onSuccess: () => form.reset() });
    };

    if (code) {
        return (
            <div className="rounded-(--radius-control) border border-line bg-white px-4 py-3 text-sm">
                <div className="flex items-center justify-between gap-3">
                    <span>
                        {t('Code')} <strong className="font-semibold">{code}</strong>
                    </span>
                    <button
                        type="button"
                        className="text-stone underline underline-offset-4 hover:text-clay"
                        onClick={() => router.delete(to('/cart/coupon'), { preserveScroll: true })}
                    >
                        {t('Remove')}
                    </button>
                </div>
                {error && (
                    <p className="mt-1.5 text-clay" role="alert">
                        {error}
                    </p>
                )}
            </div>
        );
    }

    return (
        <form onSubmit={submit} className="flex flex-col gap-1.5">
            <label htmlFor="coupon" className="text-sm font-medium">
                {t('Discount code')}
            </label>
            <div className="flex gap-2">
                <Input
                    id="coupon"
                    value={form.data.code}
                    onChange={(e) => form.setData('code', e.target.value)}
                    autoComplete="off"
                    autoCapitalize="characters"
                    maxLength={40}
                    aria-invalid={form.errors.code ? true : undefined}
                    aria-describedby={form.errors.code ? 'coupon-error' : undefined}
                />
                <Button type="submit" variant="secondary" loading={form.processing} disabled={!form.data.code.trim()}>
                    {t('Apply')}
                </Button>
            </div>
            {form.errors.code && (
                <p id="coupon-error" className="text-sm text-clay" role="alert">
                    {form.errors.code}
                </p>
            )}
        </form>
    );
}
