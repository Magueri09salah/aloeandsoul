/**
 * Display formatting for integer minor-unit amounts. All arithmetic stays in
 * integers; this module only turns them into text. Totals are computed on
 * the server — never here.
 */

const EXPONENTS: Record<string, number> = {
    JPY: 0,
    KRW: 0,
    XOF: 0,
    XAF: 0,
    BHD: 3,
    KWD: 3,
    OMR: 3,
    TND: 3,
    JOD: 3,
};

const PREFIX_SYMBOLS: Record<string, string> = { EUR: '€', USD: '$', GBP: '£' };

const grouping = new Intl.NumberFormat('en', { maximumFractionDigits: 0 });
const NBSP = String.fromCharCode(160);

export function currencyExponent(currency: string): number {
    return EXPONENTS[currency.toUpperCase()] ?? 2;
}

/**
 * 29000 MAD → "290 MAD", 28950 MAD → "289.50 MAD", 1299 EUR → "€12.99".
 * Whole amounts drop the decimals for a calmer price display.
 */
export function formatMoney(minor: number, currency: string): string {
    const exponent = currencyExponent(currency);
    const divisor = 10 ** exponent;
    const negative = minor < 0;
    const abs = Math.abs(Math.trunc(minor));
    const whole = Math.floor(abs / divisor);
    const fraction = abs % divisor;

    const number =
        grouping.format(whole) + (exponent > 0 && fraction !== 0 ? '.' + String(fraction).padStart(exponent, '0') : '');
    const symbol = PREFIX_SYMBOLS[currency.toUpperCase()];
    const text = symbol ? `${symbol}${number}` : `${number}${NBSP}${currency.toUpperCase()}`;

    return negative ? `−${text}` : text;
}

/** Minor units → decimal string for an input field ("289.50"). */
export function toDecimalInput(minor: number | null, currency: string): string {
    if (minor === null) return '';
    const exponent = currencyExponent(currency);
    if (exponent === 0) return String(minor);
    const divisor = 10 ** exponent;
    return `${Math.floor(minor / divisor)}.${String(minor % divisor).padStart(exponent, '0')}`;
}
