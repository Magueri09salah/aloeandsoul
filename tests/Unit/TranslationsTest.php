<?php

use App\Enums\OrderStatus;
use App\Http\Presenters\ProductPresenter;
use App\Http\Requests\Storefront\NewsletterRequest;
use App\Services\Settings\Settings;
use App\Support\Locales;

/**
 * Every English sentence the code translates — t()/tc() in the storefront
 * scripts, __() on the server — must have a French version in lang/fr.json.
 *
 * @return array<string, list<string>> key => files using it
 */
function translationKeys(): array
{
    $keys = [];
    $scan = function (string $dir, string $extensions, string $pattern) use (&$keys) {
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS));
        foreach ($files as $file) {
            if (! preg_match("/\.({$extensions})$/", $file->getFilename())) {
                continue;
            }
            preg_match_all($pattern, (string) file_get_contents($file->getPathname()), $matches);
            foreach ($matches[1] as $key) {
                $keys[stripcslashes($key)][] = $file->getFilename();
            }
        }
    };

    $scan(resource_path('js'), 'tsx?', "/\\b(?:t|tc)\\(\\s*'((?:[^'\\\\]|\\\\.)*)'/");
    $scan(app_path(), 'php', "/(?:__|\\\$tooMany)\\(\\s*'((?:[^'\\\\]|\\\\.)*)'/");
    $scan(resource_path('views'), 'php', "/__\\(\\s*'((?:[^'\\\\]|\\\\.)*)'/");

    // Strings translated through a variable.
    foreach ([...ProductPresenter::SECTIONS, NewsletterRequest::CONSENT_TEXT] as $key) {
        $keys[$key][] = 'dynamic';
    }
    foreach (OrderStatus::cases() as $status) {
        $keys[$status->label()][] = 'OrderStatus';
        $keys[$status->customerMessage()][] = 'OrderStatus';
    }

    return $keys;
}

/** @return array<string, string> */
function frenchStrings(): array
{
    return json_decode((string) file_get_contents(lang_path('fr.json')), true, flags: JSON_THROW_ON_ERROR);
}

it('has a French translation for every interface string', function () {
    $missing = array_diff_key(translationKeys(), frenchStrings());

    expect(array_keys($missing))->toBe([]);
});

it('keeps the same placeholders in French', function () {
    $placeholders = function (string $text): array {
        preg_match_all('/:([a-z_]+)/', $text, $m);
        $names = array_unique($m[1]);
        sort($names);

        return $names;
    };

    foreach (frenchStrings() as $english => $french) {
        expect($placeholders($french))->toBe($placeholders($english), "Placeholders differ for “{$english}”");
        expect(substr_count($french, '|'))->toBe(substr_count($english, '|'), "Plural forms differ for “{$english}”");
    }
});

it('has French validation messages for every rule', function () {
    $english = require base_path('vendor/laravel/framework/src/Illuminate/Translation/lang/en/validation.php');
    $french = require lang_path('fr/validation.php');

    expect(array_diff(array_keys($english), array_keys($french)))->toBe([]);
});

describe('locale paths', function () {
    it('moves a path between languages', function (string $path, string $locale, string $expected) {
        expect(Locales::path($path, $locale))->toBe($expected);
    })->with([
        ['/', 'fr', '/fr'],
        ['/shop', 'fr', '/fr/shop'],
        ['/shop?q=aloe', 'fr', '/fr/shop?q=aloe'],
        ['/fr', 'en', '/'],
        ['/fr/shop/beauty', 'en', '/shop/beauty'],
        ['/fr/shop', 'fr', '/fr/shop'],
        ['/french-press', 'en', '/french-press'],
    ]);

    it('reads the language from the first segment only', function () {
        expect(Locales::fromPath('/fr/shop'))->toBe('fr')
            ->and(Locales::fromPath('fr'))->toBe('fr')
            ->and(Locales::fromPath('/fresh'))->toBe('en')
            ->and(Locales::fromPath('/admin/products'))->toBe('en');
    });

    it('swaps absolute URLs', function () {
        expect(Locales::url('https://aloeandsoul.com/fr/blog/a-post', 'en'))->toBe('https://aloeandsoul.com/blog/a-post')
            ->and(Locales::url('https://aloeandsoul.com/blog?page=2', 'fr'))->toBe('https://aloeandsoul.com/fr/blog?page=2');
    });
});

describe('localized settings', function () {
    it('uses a filled French text, also inside lists, and drops the translation keys', function () {
        $values = [
            'heading' => 'Hello',
            'heading_fr' => 'Bonjour',
            'intro' => 'Welcome',
            'intro_fr' => '',
            'chapters' => [['title' => 'One', 'title_fr' => 'Un'], ['title' => 'Two', 'title_fr' => null]],
            'image' => ['path' => 'content/a', 'widths' => [480]],
        ];

        expect(Settings::localize($values, 'fr'))->toBe([
            'heading' => 'Bonjour',
            'intro' => 'Welcome',
            'chapters' => [['title' => 'Un'], ['title' => 'Two']],
            'image' => ['path' => 'content/a', 'widths' => [480]],
        ])->and(Settings::localize($values, 'en'))->toBe([
            'heading' => 'Hello',
            'intro' => 'Welcome',
            'chapters' => [['title' => 'One'], ['title' => 'Two']],
            'image' => ['path' => 'content/a', 'widths' => [480]],
        ]);
    });
});
