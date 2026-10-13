<?php

use App\Support\Phone;

it('normalises Moroccan numbers to E.164', function (string $input) {
    expect(Phone::normalize($input, 'MA'))->toBe('+212612345678');
})->with([
    '0612345678',
    '06 12 34 56 78',
    '06-12-34-56-78',
    '+212612345678',
    '+212 6 12 34 56 78',
    '00212612345678',
    '212612345678',
]);

it('accepts Moroccan landlines', function () {
    expect(Phone::normalize('0524 43 21 00', 'MA'))->toBe('+212524432100');
});

it('rejects invalid numbers', function (?string $input) {
    expect(Phone::normalize($input, 'MA'))->toBeNull();
})->with(['123', '06123', 'abcdefghij', '', null, '0012345']);

it('follows the configured country', function () {
    expect(Phone::normalize('020 7946 0018', 'GB'))->toBe('+442079460018')
        ->and(Phone::normalize('020 7946 0018', 'MA'))->toBeNull();
});

it('formats numbers for display', function () {
    expect(Phone::display('+212612345678', 'MA'))->toBe('06 12 34 56 78')
        ->and(Phone::whatsappDigits('+212612345678'))->toBe('212612345678');
});
