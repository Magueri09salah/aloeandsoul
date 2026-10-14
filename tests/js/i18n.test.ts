import { localizePath, translate, translateChoice } from '@/lib/i18n';
import { formatMoney } from '@/lib/money';

const NB = String.fromCharCode(160);

describe('translate', () => {
    it('returns the English sentence in English and its French version in French', () => {
        expect(translate('en', 'Add to cart')).toBe('Add to cart');
        expect(translate('fr', 'Add to cart')).toBe('Ajouter au panier');
    });

    it('falls back to the English sentence when there is no translation', () => {
        expect(translate('fr', 'A sentence nobody translated')).toBe('A sentence nobody translated');
    });

    it('fills in placeholders, longest names first', () => {
        expect(translate('fr', 'Image :number of :count', { number: 2, count: 5 })).toBe('Image 2 sur 5');
        expect(translate('en', ':count and :counter', { count: 1, counter: 9 })).toBe('1 and 9');
    });
});

describe('translateChoice', () => {
    it('follows each language’s plural rule (0 is singular in French)', () => {
        const key = ':count product|:count products';
        expect(translateChoice('en', key, 0)).toBe('0 products');
        expect(translateChoice('en', key, 1)).toBe('1 product');
        expect(translateChoice('en', key, 3)).toBe('3 products');
        expect(translateChoice('fr', key, 0)).toBe('0 produit');
        expect(translateChoice('fr', key, 1)).toBe('1 produit');
        expect(translateChoice('fr', key, 3)).toBe('3 produits');
    });
});

describe('localizePath', () => {
    it('prefixes storefront paths with the language', () => {
        expect(localizePath('/shop', 'fr')).toBe('/fr/shop');
        expect(localizePath('/', 'fr')).toBe('/fr');
        expect(localizePath('/our-story#wellness', 'fr')).toBe('/fr/our-story#wellness');
        expect(localizePath('/fr/shop', 'fr')).toBe('/fr/shop');
    });

    it('leaves English paths and other sites alone', () => {
        expect(localizePath('/shop', 'en')).toBe('/shop');
        expect(localizePath('https://wa.me/212600000000', 'fr')).toBe('https://wa.me/212600000000');
        expect(localizePath('//cdn.example.com/a.png', 'fr')).toBe('//cdn.example.com/a.png');
        expect(localizePath('/french-press', 'fr')).toBe('/fr/french-press');
    });
});

describe('formatMoney in French', () => {
    it('uses French digit grouping and decimal comma', () => {
        expect(formatMoney(107700, 'MAD', 'fr').replaceAll(' ', ' ')).toBe(`1 077${NB}MAD`);
        expect(formatMoney(28950, 'MAD', 'fr')).toBe(`289,50${NB}MAD`);
        expect(formatMoney(1299, 'EUR', 'fr')).toBe(`12,99${NB}€`);
    });
});
