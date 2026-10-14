import { Link } from '@inertiajs/react';

import { Accordion, AccordionContent, AccordionItem, AccordionTrigger } from '@/components/ui/accordion';
import { useI18n } from '@/lib/i18n';

export default function Faq({ faqs }: { faqs: { id: number; question: string; answer: string }[] }) {
    const { t, to } = useI18n();

    return (
        <div className="container-page grid gap-10 py-12 lg:grid-cols-12 lg:py-20">
            <div className="lg:col-span-4">
                <h1 className="font-display text-display-sm">{t('Frequently asked questions')}</h1>
                <p className="mt-4 text-stone">
                    {t('Can’t find your answer?')}{' '}
                    <Link href={to('/contact')} className="underline underline-offset-4 hover:text-aloe">
                        {t('Contact us')}
                    </Link>
                    .
                </p>
            </div>
            {faqs.length === 0 ? (
                <p className="text-stone lg:col-span-7 lg:col-start-6">{t('Questions and answers will appear here soon.')}</p>
            ) : (
                <Accordion type="multiple" className="border-t border-line lg:col-span-7 lg:col-start-6">
                    {faqs.map((f) => (
                        <AccordionItem key={f.id} value={String(f.id)}>
                            <AccordionTrigger>{f.question}</AccordionTrigger>
                            <AccordionContent className="max-w-prose whitespace-pre-line">{f.answer}</AccordionContent>
                        </AccordionItem>
                    ))}
                </Accordion>
            )}
        </div>
    );
}
