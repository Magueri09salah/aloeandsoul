<?php

namespace Database\Seeders;

use App\Enums\ProductStatus;
use App\Models\Category;
use App\Models\Product;
use Database\Seeders\Concerns\FillsBlankTranslations;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * The four categories and the client's product list. Products are created as
 * drafts with names only: no price, stock, description or image is invented.
 * The admin completes and publishes each one.
 *
 * French names and texts are filled in when they are still empty, also on
 * an existing catalogue, so re-running the seeder adds them without
 * touching anything edited in the admin.
 */
class CatalogSeeder extends Seeder
{
    use FillsBlankTranslations;

    /** @var array<string, array{name: string, tagline: string, description: string, name_fr: string, tagline_fr: string, description_fr: string, tint: string, motif: string, products: list<string|array{name: string, option: string, variants: list<string>}>}> */
    public const CATALOG = [
        'sport-fitness' => [
            'name' => 'Sport & Fitness',
            'tagline' => 'For training days and active routines.',
            'description' => 'Lotions, protein and supplements for people who train and move. Each product page lists what the product is and how it is supplied.',
            'name_fr' => 'Sport et fitness',
            'tagline_fr' => 'Pour les jours d’entraînement et les routines actives.',
            'description_fr' => 'Lotions, protéines et compléments pour celles et ceux qui s’entraînent et bougent. Chaque fiche produit indique ce qu’est le produit et sous quelle forme il est présenté.',
            'tint' => '#EDE3D1',
            'motif' => 'horizon',
            'products' => [
                'Heat Lotion',
                'Arginine',
                ['name' => 'C9', 'option' => 'Flavour', 'variants' => ['Vanilla', 'Chocolate']],
                'Vanilla Protein Ultra',
                'Chocolate Protein Ultra',
            ],
        ],
        'beauty' => [
            'name' => 'Beauty',
            'tagline' => 'Skincare for your daily routine.',
            'description' => 'Masks, creams and skincare to build a simple, considered routine.',
            'name_fr' => 'Beauté',
            'tagline_fr' => 'Des soins pour votre routine quotidienne.',
            'description_fr' => 'Masques, crèmes et soins pour construire une routine simple et réfléchie.',
            'tint' => '#EFDFD6',
            'motif' => 'circle',
            'products' => [
                'Marine Collagen',
                'Aloe Cellulose Bio Mask',
                'Eye Contour Cream',
                'Intense Hydrating Care',
                'Three-Step Routine',
                'Firming Complex',
            ],
        ],
        'hygiene' => [
            'name' => 'Hygiene',
            'tagline' => 'Everyday care for body and hair.',
            'description' => 'Deodorant, toothpaste, shampoo, conditioner and body care for every day.',
            'name_fr' => 'Hygiène',
            'tagline_fr' => 'Le soin du corps et des cheveux au quotidien.',
            'description_fr' => 'Déodorant, dentifrice, shampooing, après-shampooing et soins du corps pour tous les jours.',
            'tint' => '#DFE6E4',
            'motif' => 'wave',
            'products' => [
                'Deodorant',
                'Toothpaste',
                'Shampoo',
                'After-Shampoo / Conditioner',
                'Aloe Body Wash',
                'Aloe Body Lotion',
            ],
        ],
        'nutrition-supplements' => [
            'name' => 'Nutrition & Supplements',
            'tagline' => 'Aloe drinks and food supplements.',
            'description' => 'Aloe vera drinks and food supplements. Food supplements should not replace a varied, balanced diet and a healthy lifestyle.',
            'name_fr' => 'Nutrition et compléments',
            'tagline_fr' => 'Boissons à l’aloe et compléments alimentaires.',
            'description_fr' => 'Boissons à l’aloe vera et compléments alimentaires. Les compléments alimentaires ne doivent pas se substituer à une alimentation variée et équilibrée et à un mode de vie sain.',
            'tint' => '#E1E7DA',
            'motif' => 'leaf',
            'products' => [
                'Aloe Vera Gel',
                'Aloe Mango',
                'Aloe Peach',
                'Aloe Berry',
                'Bee Propolis',
                'Arctic Sea',
                'Forever Vision',
                'Forever Daily',
                'Forever Calcium',
            ],
        ],
    ];

    /**
     * French product names, by slug. Descriptive names are translated; brand
     * names (Aloe Vera Gel, Arctic Sea, Forever Daily…) stay as they are.
     */
    public const PRODUCT_NAMES_FR = [
        'heat-lotion' => 'Lotion chauffante',
        'arginine' => 'Arginine',
        'c9' => 'C9',
        'vanilla-protein-ultra' => 'Protéine Ultra Vanille',
        'chocolate-protein-ultra' => 'Protéine Ultra Chocolat',
        'marine-collagen' => 'Collagène marin',
        'aloe-cellulose-bio-mask' => 'Masque bio-cellulose à l’aloe',
        'eye-contour-cream' => 'Crème contour des yeux',
        'intense-hydrating-care' => 'Soin hydratant intense',
        'three-step-routine' => 'Routine en trois étapes',
        'firming-complex' => 'Complexe raffermissant',
        'deodorant' => 'Déodorant',
        'toothpaste' => 'Dentifrice',
        'shampoo' => 'Shampooing',
        'after-shampoo-conditioner' => 'Après-shampooing',
        'aloe-body-wash' => 'Gel douche à l’aloe',
        'aloe-body-lotion' => 'Lait corporel à l’aloe',
        'aloe-vera-gel' => 'Aloe Vera Gel',
        'aloe-mango' => 'Aloe Mango',
        'aloe-peach' => 'Aloe Peach',
        'aloe-berry' => 'Aloe Berry',
        'bee-propolis' => 'Propolis d’abeille',
        'arctic-sea' => 'Arctic Sea',
        'forever-vision' => 'Forever Vision',
        'forever-daily' => 'Forever Daily',
        'forever-calcium' => 'Forever Calcium',
    ];

    /** French option labels and option names. */
    private const OPTIONS_FR = [
        'Flavour' => 'Saveur',
        'Vanilla' => 'Vanille',
        'Chocolate' => 'Chocolat',
    ];

    public function run(): void
    {
        $categoryOrder = 0;
        foreach (self::CATALOG as $slug => $data) {
            $category = Category::query()->firstOrCreate(['slug' => $slug], [
                'name' => $data['name'],
                'tagline' => $data['tagline'],
                'description' => $data['description'],
                'tint' => $data['tint'],
                'motif' => $data['motif'],
                'sort_order' => $categoryOrder++,
                'is_active' => true,
            ]);
            // A translation only matches the original English text.
            self::fillBlank($category, [
                'name_fr' => $category->name === $data['name'] ? $data['name_fr'] : null,
                'tagline_fr' => $category->tagline === $data['tagline'] ? $data['tagline_fr'] : null,
                'description_fr' => $category->description === $data['description'] ? $data['description_fr'] : null,
            ]);

            foreach ($data['products'] as $position => $entry) {
                $name = is_array($entry) ? $entry['name'] : $entry;
                $productSlug = Str::slug($name);

                $product = Product::query()->where('slug', $productSlug)->first();
                if ($product === null) {
                    $product = Product::query()->create([
                        'category_id' => $category->id,
                        'name' => $name,
                        'slug' => $productSlug,
                        'option_label' => is_array($entry) ? $entry['option'] : null,
                        'status' => ProductStatus::Draft,
                        'sort_order' => $position,
                    ]);

                    $variants = is_array($entry) ? $entry['variants'] : [null];
                    foreach ($variants as $i => $variantName) {
                        $product->variants()->create([
                            'name' => $variantName,
                            'price_minor' => null,
                            'stock' => 0,
                            'track_stock' => true,
                            'is_active' => true,
                            'sort_order' => $i,
                        ]);
                    }
                }

                self::fillBlank($product, [
                    'name_fr' => $product->name === $name ? (self::PRODUCT_NAMES_FR[$productSlug] ?? null) : null,
                    'option_label_fr' => self::OPTIONS_FR[(string) $product->option_label] ?? null,
                ]);
                foreach ($product->variants()->get() as $variant) {
                    self::fillBlank($variant, ['name_fr' => self::OPTIONS_FR[(string) $variant->name] ?? null]);
                }
            }
        }
    }
}
