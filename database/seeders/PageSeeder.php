<?php

namespace Database\Seeders;

use App\Models\Page;
use Database\Seeders\Concerns\FillsBlankTranslations;
use Illuminate\Database\Seeder;

/**
 * Starting drafts for the policy pages, flagged `needs_review`. Text in
 * [square brackets] must be completed by the business before launch. The
 * privacy policy describes exactly what this website collects.
 *
 * The French version is added to an existing page only while its English
 * text is still this draft, so it never contradicts an edited policy.
 */
class PageSeeder extends Seeder
{
    use FillsBlankTranslations;

    public function run(): void
    {
        $french = $this->frenchPages();

        foreach ($this->pages() as $slug => [$title, $description, $body]) {
            $page = Page::query()->firstOrCreate(['slug' => $slug], [
                'title' => $title,
                'seo_description' => $description,
                'body' => trim($body),
                'is_published' => true,
                'needs_review' => true,
            ]);

            [$titleFr, $descriptionFr, $bodyFr] = $french[$slug];
            $unchanged = $page->body === trim($body);
            self::fillBlank($page, [
                'title_fr' => $page->title === $title ? $titleFr : null,
                'seo_description_fr' => $page->seo_description === $description ? $descriptionFr : null,
                'body_fr' => $unchanged ? trim($bodyFr) : null,
            ]);
        }
    }

    /** @return array<string, array{0: string, 1: string, 2: string}> */
    private function frenchPages(): array
    {
        return [
            'shipping-returns' => ['Livraison et retours', 'Zones de livraison, frais de livraison, paiement à la livraison et retours chez Aloe & Soul.', <<<'MD'
## Livraison

Nous livrons dans les zones proposées au moment de la commande. Les frais de livraison de votre zone s’affichent avant que vous ne validiez votre commande et sont inclus dans le montant payé à la livraison.

[Délais de livraison habituels — à confirmer.]

## Paiement à la livraison

Vous payez en espèces à la réception de votre commande. Vérifiez votre commande avec le livreur avant de payer. Si possible, préparez le montant exact.

## Confirmation de votre commande

[Si vous confirmez les commandes par téléphone ou sur WhatsApp avant de les envoyer, décrivez-le ici.]

## Retours et échanges

[Votre politique de retour : quels produits peuvent être retournés, dans quel délai, dans quel état, et comment demander un retour. Pour des raisons d’hygiène, les produits de soin et les produits alimentaires ouverts peuvent ne pas être repris.]

## Questions

Contactez-nous via la page Contact ou sur WhatsApp en indiquant votre référence de commande.
MD],
            'privacy-policy' => ['Politique de confidentialité', 'Comment Aloe & Soul collecte et utilise vos données personnelles lorsque vous commandez ou nous contactez.', <<<'MD'
Cette politique explique quelles données personnelles nous collectons sur ce site, pourquoi, et quels sont vos droits.

## Qui sommes-nous

[Raison sociale], [adresse], Maroc. Contact : l’adresse e-mail indiquée sur notre page Contact.

[Le cas échéant : numéro de déclaration ou d’autorisation auprès de la CNDP (Commission Nationale de contrôle de la protection des Données à caractère Personnel), conformément à la loi 09-08.]

## Ce que nous collectons

**Lorsque vous passez commande :** votre nom, votre numéro de téléphone, votre zone de livraison, votre ville, votre adresse de livraison, vos éventuelles instructions de livraison, et votre adresse e-mail si vous choisissez de la donner. Nous enregistrons aussi les produits commandés, les montants, ainsi que la date et l’heure auxquelles vous avez accepté cette politique.

**Lorsque vous nous contactez :** votre nom, votre adresse e-mail, votre numéro de téléphone si vous le donnez, et votre message.

**Lorsque vous vous inscrivez à notre newsletter :** votre adresse e-mail et la date à laquelle vous avez accepté de recevoir nos e-mails.

**Données techniques :** pour protéger le site contre les abus, nous conservons avec les commandes et les messages un code à sens unique, non réversible, calculé à partir de l’adresse IP de votre connexion. Nous ne conservons pas l’adresse IP elle-même avec votre commande.

Nous ne collectons aucune donnée de carte bancaire. Les commandes sont payées en espèces à la livraison.

## Pourquoi nous les utilisons

- Pour préparer, confirmer et livrer votre commande, et vous contacter à son sujet.
- Pour répondre à vos messages.
- Pour vous envoyer notre newsletter, uniquement si vous l’avez demandée.
- Pour prévenir la fraude et les utilisations abusives du site.
- Pour tenir la comptabilité exigée par la loi.

## Avec qui nous les partageons

Nous communiquons votre nom, votre numéro de téléphone et votre adresse de livraison au livreur qui apporte votre commande. [Nom de la société de livraison, le cas échéant.] Nous ne vendons pas vos données personnelles.

## Cookies

Ce site utilise uniquement les cookies nécessaires à son fonctionnement : un cookie de session, un cookie de sécurité qui protège les formulaires, et un cookie qui garde votre panier en mémoire pendant 30 jours au plus. Nous n’utilisons ni cookies publicitaires ni cookies de suivi.

## Durée de conservation

Les commandes sont conservées [pendant la durée exigée pour la comptabilité]. Les messages sont conservés [durée]. Les inscriptions à la newsletter sont conservées jusqu’à votre désinscription. Les paniers qui ne deviennent pas des commandes sont supprimés au bout de 30 jours.

## Vos droits

Vous pouvez demander à accéder à vos données personnelles, à les rectifier ou à les supprimer, ou vous opposer à leur utilisation, en nous contactant. Lorsque nous supprimons vos données de commandes passées, nous conservons les montants pour notre comptabilité mais retirons votre nom et vos coordonnées.

*Dernière mise à jour : [date].*
MD],
            'terms' => ['Conditions générales de vente', 'Les conditions qui s’appliquent lorsque vous commandez chez Aloe & Soul.', <<<'MD'
Ces conditions s’appliquent aux commandes passées sur ce site.

## Vendeur

[Raison sociale], [adresse], Maroc. [Informations d’immatriculation.]

## Produits et prix

Les descriptions et les prix des produits figurent sur chaque fiche produit, en [devise]. Le prix à payer est le total affiché au moment de la commande, frais de livraison de votre zone compris.

## Commandes

Votre commande est passée lorsque vous cliquez sur « Valider la commande » et recevez une référence de commande. [Nous pouvons vous appeler pour confirmer votre commande avant de l’envoyer.] Nous pouvons annuler une commande si un produit n’est plus disponible ou si nous ne parvenons pas à vous joindre pour confirmer la livraison ; vous n’avez alors rien à payer.

## Paiement

Le paiement se fait en espèces, au livreur, à la livraison de votre commande.

## Livraison

Les zones et les frais de livraison s’affichent au moment de la commande. Voir notre page Livraison et retours.

## Retours

Voir notre page Livraison et retours.

## Informations santé

Nos produits ne sont pas des médicaments. Les informations de ce site ne constituent pas un avis médical. Les compléments alimentaires ne doivent pas se substituer à une alimentation variée et équilibrée et à un mode de vie sain. Si vous êtes enceinte, allaitez, prenez des médicaments ou avez un problème de santé, demandez l’avis d’un professionnel de santé avant de prendre des compléments alimentaires.

## Droit applicable

[Droit applicable et tribunaux compétents.]

*Dernière mise à jour : [date].*
MD],
            'legal-notice' => ['Mentions légales', 'Informations légales sur l’éditeur du site Aloe & Soul.', <<<'MD'
## Éditeur

[Raison sociale]
[Forme juridique et numéro d’immatriculation / ICE]
[Adresse du siège], Maroc
E-mail : [adresse e-mail de contact]
Téléphone : [numéro de téléphone]

## Directeur de la publication

[Nom]

## Hébergement

Hostinger International Ltd. [Adresse de l’hébergeur telle qu’indiquée dans votre contrat Hostinger.]

## Propriété intellectuelle

Les textes et les photographies de ce site appartiennent à [propriétaire], sauf mention contraire. Les noms de produits et les marques appartiennent à leurs propriétaires respectifs.
MD],
        ];
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
