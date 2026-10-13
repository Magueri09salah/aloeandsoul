<?php

use App\Services\Catalog\ProductSheet;

it('reads front matter, sections, flavour references and images', function () {
    $sheet = ProductSheet::parse(<<<'MD'
        ---
        reference:
        variants: Vanilla=475; Chocolate = 476
        price: 425
        ---

        # Description
        First paragraph.

        ### A sub-heading
        - a list

        # How to use
        Shake well.

        # Images
        - 01.webp | Bottle on a table
        - 02.webp | Nutrition table
        MD);

    expect($sheet->meta('price'))->toBe('425')
        ->and($sheet->meta('reference'))->toBeNull()
        ->and($sheet->variants())->toBe(['Vanilla' => '475', 'Chocolate' => '476'])
        ->and($sheet->section('Description'))->toBe("First paragraph.\n\n### A sub-heading\n- a list")
        ->and($sheet->section('how to use'))->toBe('Shake well.')
        ->and($sheet->section('benefits'))->toBeNull()
        ->and($sheet->images())->toBe([
            ['file' => '01.webp', 'alt' => 'Bottle on a table'],
            ['file' => '02.webp', 'alt' => 'Nutrition table'],
        ]);
});

it('refuses a file without front matter', function () {
    ProductSheet::parse("# Description\nText");
})->throws(InvalidArgumentException::class);
