<?php

namespace Database\Seeders;

use App\Models\Faq;
use Database\Seeders\Concerns\FillsBlankTranslations;
use Illuminate\Database\Seeder;

/**
 * Answers that describe how this website actually works. Questions that
 * depend on business policy (delivery areas, returns) are created
 * unpublished until the client writes them.
 */
class FaqSeeder extends Seeder
{
    use FillsBlankTranslations;

    /** @return list<array{0: string, 1: string, 2: string, 3: string, 4: bool, 5: bool}> question, answer, French question, French answer, published, on home page */
    private function faqs(): array
    {
        $n = "\u{00A0}";

        return [
            [
                'How do I pay for my order?',
                'You pay in cash when your order is delivered. You do not need a bank card, and no payment details are requested on this website.',
                "Comment payer ma commande{$n}?",
                'Vous payez en espèces à la livraison de votre commande. Vous n’avez pas besoin de carte bancaire, et aucune information de paiement n’est demandée sur ce site.',
                true, true,
            ],
            [
                'Do I need to create an account?',
                'No. Add products to your cart, enter your name, phone number and delivery address, and place your order.',
                "Dois-je créer un compte{$n}?",
                'Non. Ajoutez des produits à votre panier, indiquez votre nom, votre numéro de téléphone et votre adresse de livraison, puis validez votre commande.',
                true, true,
            ],
            [
                'How will I know my order has been received?',
                'After you place your order you will see an order reference, for example AS-2610-K7Q3X. Keep it: you can use it with your phone number on the Track order page.',
                "Comment savoir si ma commande a bien été reçue{$n}?",
                "Après votre commande, une référence s’affiche, par exemple AS-2610-K7Q3X. Conservez-la{$n}: avec votre numéro de téléphone, elle vous permet de suivre votre commande sur la page Suivre une commande.",
                true, true,
            ],
            [
                'Can I change or cancel my order?',
                'Contact us as soon as possible by WhatsApp or through the contact page, with your order reference. Orders that have not yet been sent can be changed or cancelled.',
                "Puis-je modifier ou annuler ma commande{$n}?",
                'Contactez-nous au plus vite sur WhatsApp ou via la page Contact, en indiquant votre référence de commande. Les commandes qui ne sont pas encore parties peuvent être modifiées ou annulées.',
                true, false,
            ],
            [
                'Which areas do you deliver to, and how much does delivery cost?',
                'The delivery areas and fees are shown at checkout before you place your order.',
                "Dans quelles zones livrez-vous, et combien coûte la livraison{$n}?",
                'Les zones et les frais de livraison s’affichent au moment de finaliser la commande, avant que vous ne la validiez.',
                false, true,
            ],
            [
                'What is your returns policy?',
                'Please see the Shipping and returns page.',
                "Quelle est votre politique de retour{$n}?",
                'Consultez la page Livraison et retours.',
                false, false,
            ],
        ];
    }

    public function run(): void
    {
        // On an existing list, only add the French version of the original questions.
        if (Faq::query()->exists()) {
            foreach ($this->faqs() as [$question, $answer, $questionFr, $answerFr]) {
                $faq = Faq::query()->where('question', $question)->first();
                if ($faq !== null) {
                    self::fillBlank($faq, [
                        'question_fr' => $questionFr,
                        // Only when the English answer is still the original one.
                        'answer_fr' => $faq->answer === $answer ? $answerFr : null,
                    ]);
                }
            }

            return;
        }

        foreach ($this->faqs() as $i => [$question, $answer, $questionFr, $answerFr, $published, $home]) {
            Faq::query()->create([
                'question' => $question,
                'answer' => $answer,
                'question_fr' => $questionFr,
                'answer_fr' => $answerFr,
                'sort_order' => $i,
                'is_published' => $published,
                'show_on_home' => $home,
            ]);
        }
    }
}
