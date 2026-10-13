import { useEffect, useMemo } from 'react';

/** Temporary URL to preview a chosen file; released when it changes or unmounts. */
export function useObjectUrl(file: File | null): string | null {
    const url = useMemo(() => (file ? URL.createObjectURL(file) : null), [file]);

    useEffect(
        () => () => {
            if (url) URL.revokeObjectURL(url);
        },
        [url],
    );

    return url;
}
