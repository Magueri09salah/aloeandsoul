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
            'currency' => 'MAD',
            'country' => 'MA',
            'country_name' => 'Morocco',
            'order_prefix' => 'AS',
            'low_stock_threshold' => 5,
            'max_quantity_per_line' => 10,
            'ordering_enabled' => true,
            'closed_message' => 'Online ordering is paused for a short while. Please contact us on WhatsApp to place an order.',
        ],

        'checkout' => [
            'cod_instructions' => 'You pay in cash when your order is delivered. No card or online payment is needed.',
            'confirmation_note' => 'We will contact you by phone or WhatsApp to confirm your order before it is sent.', // [CLIENT] confirm this practice
            'delivery_info' => '', // [CLIENT] e.g. typical delivery times
        ],

        'contact' => [
            'email' => 'aloeandsoul@gmail.com',
            'phone' => '+212612033430',
            'whatsapp' => '+212612033430',
            'locations' => ['Marrakech', 'Essaouira', 'Casablanca'],
            'hours' => '',
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
            'link_label' => '',
            'link_url' => '',
        ],

        'seo' => [
            'title' => 'Aloe & Soul — Nourish the Body. Inspire the Soul.',
            'description' => 'Wellness, beauty, fitness and nutrition products chosen by the founder of Aloe & Soul, whose journey led from London to Marrakech. Order online and pay in cash on delivery.',
        ],

        'home' => [
            'hero_heading' => 'Nourish the body. Inspire the soul.',
            'hero_text' => 'Wellness, for me, is not about being perfect. It is about balance, kindness towards ourselves and making small choices that help us feel stronger, healthier and more alive.',
            'hero_attribution' => 'The founder of Aloe & Soul',
            'hero_image' => null, // null = bundled founder photograph
            'hero_image_alt' => 'The founder of Aloe & Soul walking on the dunes near Essaouira at sunset',
            'intro_heading' => 'There is another way to build a life',
            'intro_text' => 'Aloe & Soul began with a search for answers and became a way of living: more conscious of what we put into our bodies, how we care for ourselves and how we choose to live.',
            'featured_heading' => 'Chosen by the founder',
            'ritual_enabled' => false,
            'ritual_heading' => 'A simple routine',
            'ritual_intro' => '',
            'ritual_steps' => [], // [{product_id, note}]
            'newsletter_enabled' => true,
            'collaborators_enabled' => true,
            'collaborators_heading' => 'In collaboration with',
            'collaborators_intro' => '',
            'blog_enabled' => true,
            'blog_heading' => 'From the blog',
        ],

        'blog' => [
            'heading' => 'Blog',
            'intro' => 'News, stories and thoughts from Aloe & Soul.',
        ],

        'story' => [
            'heading' => 'From London to Marrakech',
            'intro' => 'I didn’t plan to become an entrepreneur, I simply said yes to an opportunity.',
            'image' => null,
            'image_alt' => 'The founder of Aloe & Soul on the dunes near Essaouira',
            'milestones' => [
                ['place' => 'London', 'text' => 'More than twenty years: corporate life, interior architecture, art galleries and business.'],
                ['place' => 'Marrakech', 'text' => 'A new beginning in Morocco, building Chris Immo and working with properties and riads.'],
                ['place' => 'Aloe & Soul', 'text' => 'Wellbeing and personal growth, shared from Morocco.'],
            ],
            'chapters' => [
                [
                    'key' => 'wellness',
                    'title' => 'My wellness journey',
                    'body' => "My journey into wellness did not begin with a perfect lifestyle or a grand plan.\n\nIt began during a difficult chapter of my life, when stress and painful experiences started showing on my skin. Living in London at the time, I developed severe rosacea and, like many women, began searching for ways to feel better—inside and out.\n\nI wasn’t looking for a business. I was simply looking for answers.\n\nDiscovering natural products became the beginning of a much deeper journey. Slowly, I became more conscious of what I put into my body, how I cared for myself and how closely our wellbeing is connected to the way we live, think and feel.\n\nToday, wellness, for me, is not about being perfect. It is about balance, kindness towards ourselves and making small choices that help us feel stronger, healthier and more alive.\n\nThat is the soul behind Aloe & Soul: sharing a journey of wellbeing, naturally and honestly.",
                ],
                [
                    'key' => 'business',
                    'title' => 'My business journey',
                    'body' => "My business journey has never followed a traditional path.\n\nFrom London to Marrakech, I have constantly reinvented myself, guided by curiosity, creativity and a deep desire for independence.\n\nI have worked in corporate life, studied interior architecture, opened art galleries and built a career in real estate. Each chapter taught me something different, but they all had one thing in common: a love for people, beautiful spaces, new ideas and creating opportunities.\n\nI have learned that building a business is rarely a straight line. There are successes, disappointments, unexpected turns and moments when you have to begin again.\n\nBut perhaps that is the beauty of the journey.\n\nToday, I believe business should not only be about making a living. It should also create possibilities, connections and freedom.\n\nThrough Aloe & Soul, I share my experience and my belief that it is never too late to reinvent yourself, create something meaningful and build a life that truly reflects who you are.",
                ],
                [
                    'key' => 'freedom',
                    'title' => 'My freedom journey',
                    'body' => "Freedom has always been one of the strongest forces guiding my life.\n\nIt has taken me from London to Marrakech, through different careers, countries, businesses and unexpected new beginnings.\n\nFor me, freedom is not simply about having more time or more money. It is about having choices. The freedom to reinvent yourself, to change direction, to work differently and to create a life that feels true to who you are.\n\nIt has taken courage to leave familiar paths and begin again more than once. But every new chapter has taught me that we are never too old, too late or too far along to create something different.\n\nToday, my vision of freedom is simple: to build a life with more independence, more meaningful connections, better wellbeing and the space to enjoy the journey along the way.\n\nAloe & Soul is part of that vision.\n\nA reminder that life does not have to be lived according to someone else’s expectations. We can evolve, reinvent ourselves and, little by little, create a life that feels like our own.",
                ],
            ],
            'closing' => 'Whether you are here for skincare, nutrition, fitness, new experiences or the idea of building something of your own, you are welcome.',
            'disclaimer' => 'This is a personal story. It describes one person’s experience and is not medical advice. Products sold by Aloe & Soul are not intended to diagnose, treat, cure or prevent any disease.',
        ],
    ],
];
