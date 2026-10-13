import { router, useForm } from '@inertiajs/react';
import { Upload } from 'lucide-react';

import type { ImageData } from '@/types';

import { Button } from '@/components/ui/button';

/** Replace or reset an editorial image (home hero, story). */
export function ContentImage({ slot, image, isCustom }: { slot: 'hero' | 'story'; image: ImageData; isCustom: boolean }) {
    const form = useForm<{ image: File | null }>({ image: null });

    return (
        <div className="flex flex-col gap-4 sm:flex-row">
            <div className="arch aspect-[3/4] w-36 shrink-0 bg-sand">
                <img src={image.src} alt="" className="h-full w-full object-cover" />
            </div>
            <form
                className="flex-1 space-y-3"
                onSubmit={(e) => {
                    e.preventDefault();
                    form.post(`/admin/content/images/${slot}`, { forceFormData: true, preserveScroll: true, onSuccess: () => form.reset() });
                }}
            >
                <p className="text-sm text-stone">Portrait photographs work best (3:4), at least 800 px wide. JPG, PNG or WebP.</p>
                <label className="flex cursor-pointer items-center gap-2 rounded-md border border-dashed border-stone/40 px-4 py-3 text-sm hover:border-ink">
                    <Upload className="size-4 text-stone" aria-hidden="true" />
                    {form.data.image ? form.data.image.name : 'Choose a new photograph'}
                    <input type="file" accept="image/jpeg,image/png,image/webp" className="sr-only" onChange={(e) => form.setData('image', e.target.files?.[0] ?? null)} />
                </label>
                {form.errors.image && (
                    <p className="text-sm text-clay" role="alert">
                        {form.errors.image}
                    </p>
                )}
                <div className="flex gap-2">
                    <Button type="submit" size="sm" loading={form.processing} disabled={!form.data.image}>
                        Upload
                    </Button>
                    {isCustom && (
                        <Button type="button" size="sm" variant="ghost" onClick={() => router.delete(`/admin/content/images/${slot}`, { preserveScroll: true })}>
                            Use the original photograph
                        </Button>
                    )}
                </div>
            </form>
        </div>
    );
}
