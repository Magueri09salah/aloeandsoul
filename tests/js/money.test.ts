import { currencyExponent, formatMoney, toDecimalInput } from '@/lib/money';

const NB = String.fromCharCode(160); // no-break space between amount and currency

describe('formatMoney', () => {
    it('formats whole dirham amounts without decimals', () => {
        expect(formatMoney(29000, 'MAD')).toBe(`290${NB}MAD`);
        expect(formatMoney(120000, 'MAD')).toBe(`1,200${NB}MAD`);
    });

    it('keeps centimes when present', () => {
        expect(formatMoney(28950, 'MAD')).toBe(`289.50${NB}MAD`);
        expect(formatMoney(5, 'MAD')).toBe(`0.05${NB}MAD`);
    });

    it('uses symbols for euro, dollar and pound', () => {
        expect(formatMoney(1299, 'EUR')).toBe('€12.99');
        expect(formatMoney(1000, 'GBP')).toBe('£10');
    });

    it('handles zero-decimal and three-decimal currencies', () => {
        expect(currencyExponent('JPY')).toBe(0);
        expect(formatMoney(1500, 'JPY')).toBe(`1,500${NB}JPY`);
        expect(formatMoney(1250, 'TND')).toBe(`1.250${NB}TND`);
    });

    it('marks negative amounts', () => {
        expect(formatMoney(-2000, 'MAD')).toBe(`−20${NB}MAD`);
    });
});

describe('toDecimalInput', () => {
    it('produces a value suitable for a form field', () => {
        expect(toDecimalInput(28950, 'MAD')).toBe('289.50');
        expect(toDecimalInput(7, 'MAD')).toBe('0.07');
        expect(toDecimalInput(null, 'MAD')).toBe('');
    });
});
