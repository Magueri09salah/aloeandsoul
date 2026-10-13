import { cn } from '@/lib/utils';

/**
 * Renders HTML produced on the server by CommonMark with raw HTML stripped
 * and unsafe links removed (ProductPresenter::markdown). This is the only
 * place in the app allowed to inject HTML; never pass user input here.
 */
export function SafeHtml({ html, className }: { html: string; className?: string }) {
    return <div className={cn(className)} dangerouslySetInnerHTML={{ __html: html }} />;
}
