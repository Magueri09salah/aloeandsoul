import { Link } from '@inertiajs/react';

import { Accordion, AccordionContent, AccordionItem, AccordionTrigger } from '@/components/ui/accordion';

export default function Faq({ faqs }: { faqs: { id: number; question: string; answer: string }[] }) {
    return (
        <div className="container-page grid gap-10 py-12 lg:grid-cols-12 lg:py-20">
            <div className="lg:col-span-4">
                <h1 className="font-display text-display-sm">Frequently asked questions</h1>
                <p className="mt-4 text-stone">
                    Can’t find your answer?{' '}
                    <Link href="/contact" className="underline underline-offset-4 hover:text-aloe">
                        Contact us
                    </Link>
                    .
                </p>
            </div>
            {faqs.length === 0 ? (
                <p className="text-stone lg:col-span-7 lg:col-start-6">Questions and answers will appear here soon.</p>
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
