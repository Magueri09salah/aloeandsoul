<?php

namespace Database\Seeders;

use App\Models\Faq;
use Illuminate\Database\Seeder;

/**
 * Answers that describe how this website actually works. Questions that
 * depend on business policy (delivery areas, returns) are created
 * unpublished until the client writes them.
 */
class FaqSeeder extends Seeder
{
    public function run(): void
    {
        if (Faq::query()->exists()) {
            return;
        }

        $faqs = [
            ['How do I pay for my order?', 'You pay in cash when your order is delivered. You do not need a bank card, and no payment details are requested on this website.', true, true],
            ['Do I need to create an account?', 'No. Add products to your cart, enter your name, phone number and delivery address, and place your order.', true, true],
            ['How will I know my order has been received?', 'After you place your order you will see an order reference, for example AS-2610-K7Q3X. Keep it: you can use it with your phone number on the Track order page.', true, true],
            ['Can I change or cancel my order?', 'Contact us as soon as possible by WhatsApp or through the contact page, with your order reference. Orders that have not yet been sent can be changed or cancelled.', true, false],
            ['Which areas do you deliver to, and how much does delivery cost?', 'The delivery areas and fees are shown at checkout before you place your order.', false, true],
            ['What is your returns policy?', 'Please see the Shipping and returns page.', false, false],
        ];

        foreach ($faqs as $i => [$question, $answer, $published, $home]) {
            Faq::query()->create([
                'question' => $question,
                'answer' => $answer,
                'sort_order' => $i,
                'is_published' => $published,
                'show_on_home' => $home,
            ]);
        }
    }
}
