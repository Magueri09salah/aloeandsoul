import { Motif } from '@/components/brand/motif';
import { cn } from '@/lib/utils';

/** Shown until the client uploads an official product photograph. Never a fake packshot. */
export function ProductPlaceholder({ tint, motif = 'leaf', className }: { tint: string; motif?: string; className?: string }) {
    return (
        <div className={cn('flex h-full w-full items-center justify-center', className)} style={{ backgroundColor: tint }}>
            <div className="w-2/5 max-w-36 text-dune">
                <Motif name={motif} />
            </div>
        </div>
    );
}
