<?php

/*
|--------------------------------------------------------------------------
| Store setting defaults
|--------------------------------------------------------------------------
|
| Every value here can be changed in Admin → Settings / Content and is then
| stored in the `settings` table. These defaults apply until it is changed.
| Values marked [CLIENT] are placeholders awaiting the client's confirmation.
|
*/

return [

    // Timezone used to display dates and compute "today" on the dashboard.
    'display_timezone' => env('APP_DISPLAY_TIMEZONE', 'Africa/Casablanca'),

    // Secret used to hash IP addresses (abuse signals) without storing them.
    'ip_hash_key' => env('IP_HASH_KEY', env('APP_KEY')),

    // Uploaded image renditions (px widths) and limits.
    'images' => [
        'widths' => [480, 960, 1600],
        'max_kilobytes' => 8192,
        'max_dimension' => 6000,
        'min_width' => 400,
        'quality' => 80,
    ],

    'cart' => [
        'cookie' => 'aloe_cart',
        'lifetime_days' => 30,
    ],

    'defaults' => [

        'store' => [
            'name' => 'Aloe & Soul',
            'tagline' => 'Nourish the Body. Inspire the Soul.',
            'tagline_fr' => 'Nourrir le corps. Inspirer l’âme.',
            'currency' => 'MAD',
            'country' => 'MA',
            'country_name' => 'Morocco',
            'order_prefix' => 'AS',
            'low_stock_threshold' => 5,
            'max_quantity_per_line' => 10,
            'ordering_enabled' => true,
            'closed_message' => 'Online ordering is paused for a short while. Please contact us on WhatsApp to place an order.',
            'closed_message_fr' => 'La commande en ligne est suspendue pour un court moment. Contactez-nous sur WhatsApp pour passer commande.',
        ],

        // Keys ending in "_fr" are the French versions shown on /fr pages; an
        // empty one falls back to the English text.
        'checkout' => [
            'cod_instructions' => 'You pay in cash when your order is delivered. No card or online payment is needed.',
            'cod_instructions_fr' => 'Vous payez en espèces à la livraison de votre commande. Aucune carte ni aucun paiement en ligne n’est nécessaire.',
            'confirmation_note' => 'We will contact you by phone or WhatsApp to confirm your order before it is sent.', // [CLIENT] confirm this practice
            'confirmation_note_fr' => 'Nous vous contacterons par téléphone ou sur WhatsApp pour confirmer votre commande avant son envoi.',
            'delivery_info' => '', // [CLIENT] e.g. typical delivery times
            'delivery_info_fr' => '',
        ],

        'contact' => [
            'email' => 'aloeandsoul@gmail.com',
            'phone' => '+212612033430',
            'whatsapp' => '+212612033430',
            'locations' => ['Marrakech', 'Essaouira', 'Casablanca'],
            'hours' => '',
            'hours_fr' => '',
        ],

        'social' => [
            'instagram' => '',
            'facebook' => '',
            'tiktok' => '',
            'youtube' => '',
        ],

        'announcement' => [
            'enabled' => true,
            'text' => 'Pay in cash when your order arrives.',
            'text_fr' => 'Payez en espèces à la réception de votre commande.',
            'link_label' => '',
            'link_label_fr' => '',
            'link_url' => '',
        ],

        'seo' => [
            'title' => 'Aloe & Soul — Nourish the Body. Inspire the Soul.',
            'title_fr' => 'Aloe & Soul — Nourrir le corps. Inspirer l’âme.',
            'description' => 'Wellness, beauty, fitness and nutrition products chosen by the founder of Aloe & Soul, whose journey led from London to Marrakech. Order online and pay in cash on delivery.',
            'description_fr' => 'Des produits de bien-être, de beauté, de fitness et de nutrition choisis par la fondatrice d’Aloe & Soul, dont le parcours l’a menée de Londres à Marrakech. Commandez en ligne et payez en espèces à la livraison.',
        ],

        'home' => [
            'hero_heading' => 'Nourish the body. Inspire the soul.',
            'hero_heading_fr' => 'Nourrir le corps. Inspirer l’âme.',
            'hero_text' => 'Wellness, for me, is not about being perfect. It is about balance, kindness towards ourselves and making small choices that help us feel stronger, healthier and more alive.',
            'hero_text_fr' => 'Pour moi, le bien-être n’est pas une question de perfection. C’est une question d’équilibre, de bienveillance envers soi-même et de petits choix qui nous aident à nous sentir plus forts, en meilleure santé et plus vivants.',
            'hero_attribution' => 'The founder of Aloe & Soul',
            'hero_attribution_fr' => 'La fondatrice d’Aloe & Soul',
            'hero_image' => null, // null = bundled founder photograph
            'hero_image_alt' => 'The founder of Aloe & Soul walking on the dunes near Essaouira at sunset',
            'hero_image_alt_fr' => 'La fondatrice d’Aloe & Soul marchant sur les dunes près d’Essaouira au coucher du soleil',
            'intro_heading' => 'There is another way to build a life',
            'intro_heading_fr' => 'Il existe une autre façon de construire sa vie',
            'intro_text' => 'Aloe & Soul began with a search for answers and became a way of living: more conscious of what we put into our bodies, how we care for ourselves and how we choose to live.',
            'intro_text_fr' => 'Aloe & Soul est né d’une quête de réponses et est devenu une façon de vivre : plus attentive à ce que nous apportons à notre corps, à la manière dont nous prenons soin de nous et dont nous choisissons de vivre.',
            'featured_heading' => 'Chosen by the founder',
            'featured_heading_fr' => 'Choisis par la fondatrice',
            'ritual_enabled' => false,
            'ritual_heading' => 'A simple routine',
            'ritual_heading_fr' => 'Une routine simple',
            'ritual_intro' => '',
            'ritual_intro_fr' => '',
            'ritual_steps' => [], // [{product_id, note, note_fr}]
            'newsletter_enabled' => true,
            'collaborators_enabled' => true,
            'collaborators_heading' => 'In collaboration with',
            'collaborators_heading_fr' => 'En collaboration avec',
            'collaborators_intro' => '',
            'collaborators_intro_fr' => '',
            'blog_enabled' => true,
            'blog_heading' => 'From the blog',
            'blog_heading_fr' => 'Sur le blog',
        ],

        'blog' => [
            'heading' => 'Blog',
            'heading_fr' => 'Blog',
            'intro' => 'News, stories and thoughts from Aloe & Soul.',
            'intro_fr' => 'Actualités, histoires et réflexions d’Aloe & Soul.',
        ],

        'story' => [
            'heading' => 'From London to Marrakech',
            'heading_fr' => 'De Londres à Marrakech',
            'intro' => 'I didn’t plan to become an entrepreneur, I simply said yes to an opportunity.',
            'intro_fr' => 'Je n’avais pas prévu de devenir entrepreneure, j’ai simplement dit oui à une opportunité.',
            'image' => null,
            'image_alt' => 'The founder of Aloe & Soul on the dunes near Essaouira',
            'image_alt_fr' => 'La fondatrice d’Aloe & Soul sur les dunes près d’Essaouira',
            'milestones' => [
                ['place' => 'London', 'text' => 'More than twenty years: corporate life, interior architecture, art galleries and business.', 'place_fr' => 'Londres', 'text_fr' => 'Plus de vingt ans : la vie en entreprise, l’architecture d’intérieur, les galeries d’art et les affaires.'],
                ['place' => 'Marrakech', 'text' => 'A new beginning in Morocco, building Chris Immo and working with properties and riads.', 'place_fr' => 'Marrakech', 'text_fr' => 'Un nouveau départ au Maroc, avec la création de Chris Immo et le travail autour des biens immobiliers et des riads.'],
                ['place' => 'Aloe & Soul', 'text' => 'Wellbeing and personal growth, shared from Morocco.', 'place_fr' => 'Aloe & Soul', 'text_fr' => 'Le bien-être et l’épanouissement personnel, partagés depuis le Maroc.'],
            ],
            'chapters' => [
                [
                    'key' => 'wellness',
                    'title' => 'My wellness journey',
                    'body' => "My journey into wellness did not begin with a perfect lifestyle or a grand plan.\n\nIt began during a difficult chapter of my life, when stress and painful experiences started showing on my skin. Living in London at the time, I developed severe rosacea and, like many women, began searching for ways to feel better—inside and out.\n\nI wasn’t looking for a business. I was simply looking for answers.\n\nDiscovering natural products became the beginning of a much deeper journey. Slowly, I became more conscious of what I put into my body, how I cared for myself and how closely our wellbeing is connected to the way we live, think and feel.\n\nToday, wellness, for me, is not about being perfect. It is about balance, kindness towards ourselves and making small choices that help us feel stronger, healthier and more alive.\n\nThat is the soul behind Aloe & Soul: sharing a journey of wellbeing, naturally and honestly.",
                    'title_fr' => 'Mon parcours bien-être',
                    'body_fr' => "Mon parcours vers le bien-être n’a pas commencé par un mode de vie parfait ni par un grand projet.\n\nIl a commencé pendant une période difficile de ma vie, lorsque le stress et des épreuves douloureuses ont commencé à se voir sur ma peau. Je vivais alors à Londres. J’ai développé une rosacée sévère et, comme beaucoup de femmes, j’ai cherché des moyens de me sentir mieux, à l’intérieur comme à l’extérieur.\n\nJe ne cherchais pas une activité. Je cherchais simplement des réponses.\n\nLa découverte des produits naturels a marqué le début d’un cheminement bien plus profond. Peu à peu, je suis devenue plus consciente de ce que j’apportais à mon corps, de la façon dont je prenais soin de moi et du lien étroit entre notre bien-être et notre manière de vivre, de penser et de ressentir.\n\nAujourd’hui, pour moi, le bien-être n’est pas une question de perfection. C’est une question d’équilibre, de bienveillance envers soi-même et de petits choix qui nous aident à nous sentir plus forts, en meilleure santé et plus vivants.\n\nC’est l’âme d’Aloe & Soul : partager un parcours de bien-être, naturellement et sincèrement.",
                ],
                [
                    'key' => 'business',
                    'title' => 'My business journey',
                    'body' => "My business journey has never followed a traditional path.\n\nFrom London to Marrakech, I have constantly reinvented myself, guided by curiosity, creativity and a deep desire for independence.\n\nI have worked in corporate life, studied interior architecture, opened art galleries and built a career in real estate. Each chapter taught me something different, but they all had one thing in common: a love for people, beautiful spaces, new ideas and creating opportunities.\n\nI have learned that building a business is rarely a straight line. There are successes, disappointments, unexpected turns and moments when you have to begin again.\n\nBut perhaps that is the beauty of the journey.\n\nToday, I believe business should not only be about making a living. It should also create possibilities, connections and freedom.\n\nThrough Aloe & Soul, I share my experience and my belief that it is never too late to reinvent yourself, create something meaningful and build a life that truly reflects who you are.",
                    'title_fr' => 'Mon parcours professionnel',
                    'body_fr' => "Mon parcours professionnel n’a jamais suivi un chemin traditionnel.\n\nDe Londres à Marrakech, je me suis sans cesse réinventée, guidée par la curiosité, la créativité et un profond désir d’indépendance.\n\nJ’ai travaillé en entreprise, étudié l’architecture d’intérieur, ouvert des galeries d’art et construit une carrière dans l’immobilier. Chaque chapitre m’a appris quelque chose de différent, mais tous avaient un point commun : l’amour des gens, des beaux espaces, des idées nouvelles et de la création d’opportunités.\n\nJ’ai appris que bâtir une entreprise est rarement une ligne droite. Il y a des réussites, des déceptions, des virages inattendus et des moments où il faut tout recommencer.\n\nMais c’est peut-être là toute la beauté du parcours.\n\nAujourd’hui, je crois qu’une activité ne doit pas seulement permettre de gagner sa vie. Elle doit aussi créer des possibilités, des liens et de la liberté.\n\nÀ travers Aloe & Soul, je partage mon expérience et ma conviction qu’il n’est jamais trop tard pour se réinventer, créer quelque chose qui a du sens et construire une vie qui nous ressemble vraiment.",
                ],
                [
                    'key' => 'freedom',
                    'title' => 'My freedom journey',
                    'body' => "Freedom has always been one of the strongest forces guiding my life.\n\nIt has taken me from London to Marrakech, through different careers, countries, businesses and unexpected new beginnings.\n\nFor me, freedom is not simply about having more time or more money. It is about having choices. The freedom to reinvent yourself, to change direction, to work differently and to create a life that feels true to who you are.\n\nIt has taken courage to leave familiar paths and begin again more than once. But every new chapter has taught me that we are never too old, too late or too far along to create something different.\n\nToday, my vision of freedom is simple: to build a life with more independence, more meaningful connections, better wellbeing and the space to enjoy the journey along the way.\n\nAloe & Soul is part of that vision.\n\nA reminder that life does not have to be lived according to someone else’s expectations. We can evolve, reinvent ourselves and, little by little, create a life that feels like our own.",
                    'title_fr' => 'Mon parcours vers la liberté',
                    'body_fr' => "La liberté a toujours été l’une des forces qui ont le plus guidé ma vie.\n\nElle m’a menée de Londres à Marrakech, à travers différents métiers, pays, entreprises et nouveaux départs inattendus.\n\nPour moi, la liberté ne consiste pas seulement à avoir plus de temps ou plus d’argent. Il s’agit d’avoir le choix. La liberté de se réinventer, de changer de direction, de travailler autrement et de se construire une vie qui nous ressemble.\n\nIl m’a fallu du courage pour quitter des chemins familiers et recommencer plus d’une fois. Mais chaque nouveau chapitre m’a appris qu’on n’est jamais trop âgé, qu’il n’est jamais trop tard et qu’on n’est jamais trop engagé sur un chemin pour créer quelque chose de différent.\n\nAujourd’hui, ma vision de la liberté est simple : construire une vie avec plus d’indépendance, des liens plus sincères, un meilleur bien-être et l’espace pour profiter du chemin.\n\nAloe & Soul fait partie de cette vision.\n\nUn rappel que la vie n’a pas à être vécue selon les attentes des autres. Nous pouvons évoluer, nous réinventer et, petit à petit, créer une vie qui nous appartient.",
                ],
            ],
            'closing' => 'Whether you are here for skincare, nutrition, fitness, new experiences or the idea of building something of your own, you are welcome.',
            'closing_fr' => 'Que vous veniez pour les soins de la peau, la nutrition, le fitness, de nouvelles expériences ou l’envie de construire quelque chose à vous, bienvenue.',
            'disclaimer' => 'This is a personal story. It describes one person’s experience and is not medical advice. Products sold by Aloe & Soul are not intended to diagnose, treat, cure or prevent any disease.',
            'disclaimer_fr' => 'Ceci est une histoire personnelle. Elle décrit l’expérience d’une personne et ne constitue pas un avis médical. Les produits vendus par Aloe & Soul ne sont pas destinés à diagnostiquer, traiter, guérir ou prévenir une maladie.',
        ],
    ],
];
