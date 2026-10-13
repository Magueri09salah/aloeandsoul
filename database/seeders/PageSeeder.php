<?php

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Seeder;

/**
 * Starting drafts for the policy pages, flagged `needs_review`. Text in
 * [square brackets] must be completed by the business before launch. The
 * privacy policy describes exactly what this website collects.
 */
class PageSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->pages() as $slug => [$title, $description, $body]) {
            Page::query()->firstOrCreate(['slug' => $slug], [
                'title' => $title,
                'seo_description' => $description,
                'body' => trim($body),
                'is_published' => true,
                'needs_review' => true,
            ]);
        }
    }

    /** @return array<string, array{0: string, 1: string, 2: string}> */
    private function pages(): array
    {
        return [
            'shipping-returns' => ['Shipping and returns', 'Delivery areas, delivery fees, cash on delivery and returns at Aloe & Soul.', <<<'MD'
## Delivery

We deliver to the areas listed at checkout. The delivery fee for your area is shown before you place your order and is included in the amount you pay on delivery.

[Typical delivery times — to be confirmed.]

## Paying on delivery

You pay in cash when your order arrives. Please check your order with the courier before paying. If you can, have the exact amount ready.

## Confirming your order

[If you confirm orders by phone or WhatsApp before sending them, describe it here.]

## Returns and exchanges

[Your returns policy: which products can be returned, within how many days, in what condition, and how to request a return. Note that for hygiene reasons opened personal-care and food products may not be returnable.]

## Questions

Contact us through the contact page or on WhatsApp with your order reference.
MD],
            'privacy-policy' => ['Privacy policy', 'How Aloe & Soul collects and uses your personal data when you order or contact us.', <<<'MD'
This policy explains what personal data we collect through this website, why, and what your rights are.

## Who we are

[Legal business name], [address], Morocco. Contact: the email address shown on our contact page.

[If applicable: declaration / authorisation number with the CNDP (Commission Nationale de contrôle de la protection des Données à caractère Personnel) under Law 09-08.]

## What we collect

**When you place an order:** your name, phone number, delivery area, city, delivery address, any delivery notes, and your email address if you choose to give it. We also record the products ordered, the amounts, and the date and time you accepted this policy.

**When you contact us:** your name, email address, phone number if given, and your message.

**When you subscribe to our newsletter:** your email address and the date you agreed to receive emails.

**Technical data:** to protect the website from abuse we keep a one-way, non-reversible code derived from your internet connection's IP address alongside orders and messages. We do not store the IP address itself with your order.

We do not collect payment card details. Orders are paid in cash on delivery.

## Why we use it

- To prepare, confirm and deliver your order, and to contact you about it.
- To answer your messages.
- To send you our newsletter, only if you asked for it.
- To prevent fraud and misuse of the website.
- To keep the accounting records required by law.

## Who we share it with

We share your name, phone number and delivery address with the courier who delivers your order. [Name of the delivery company, if applicable.] We do not sell your personal data.

## Cookies

This website uses only the cookies it needs to work: a session cookie, a security cookie that protects forms, and a cookie that remembers your cart for up to 30 days. We do not use advertising or tracking cookies.

## How long we keep it

Order records are kept for [the period required for accounting purposes]. Messages are kept for [period]. Newsletter subscriptions are kept until you unsubscribe. Carts that are not turned into orders are deleted after 30 days.

## Your rights

You can ask to access, correct or delete your personal data, or object to its use, by contacting us. When we delete your data from past orders, we keep the order amounts for our accounting records but remove your name and contact details.

*Last updated: [date].*
MD],
            'terms' => ['Terms of sale', 'The terms that apply when you order from Aloe & Soul.', <<<'MD'
These terms apply to orders placed on this website.

## Seller

[Legal business name], [address], Morocco. [Registration details.]

## Products and prices

Product descriptions and prices are shown on each product page in [currency]. The price you pay is the total shown at checkout, including the delivery fee for your area.

## Orders

Your order is placed when you click "Place order" and receive an order reference. [We may contact you by phone to confirm your order before sending it.] We may cancel an order if a product is no longer available or if we cannot reach you to confirm delivery details; you have nothing to pay in that case.

## Payment

Payment is made in cash, to the courier, when your order is delivered.

## Delivery

Delivery areas and fees are shown at checkout. See our Shipping and returns page.

## Returns

See our Shipping and returns page.

## Health information

Our products are not medicines. Information on this website is not medical advice. Food supplements should not replace a varied, balanced diet and a healthy lifestyle. If you are pregnant, breastfeeding, taking medication or have a medical condition, ask a health professional before using food supplements.

## Applicable law

[Governing law and competent courts.]

*Last updated: [date].*
MD],
            'legal-notice' => ['Legal notice', 'Legal information about the publisher of the Aloe & Soul website.', <<<'MD'
## Publisher

[Legal business name]
[Legal form and registration number / ICE]
[Registered address], Morocco
Email: [contact email]
Phone: [phone number]

## Publication director

[Name]

## Hosting

Hostinger International Ltd. [Hosting provider address as stated in your Hostinger contract.]

## Intellectual property

The text and photographs on this website belong to [owner] unless stated otherwise. Product names and trademarks belong to their respective owners.
MD],
        ];
    }
}
