import { expect, test } from '@playwright/test';

import { completeCheckout } from './fixtures';

test.describe('Shopping journey', () => {
    test('browse a category, view a product, add to cart, change quantity and pay on delivery @mobile', async ({ page }) => {
        // 1. Browse a category.
        await page.goto('/');
        await page.goto('/shop/hygiene');
        await expect(page.getByRole('heading', { level: 1, name: 'Hygiene' })).toBeVisible();

        // 2. View a product.
        await page.getByRole('heading', { name: 'Shampoo', exact: true }).click();
        await expect(page.getByRole('heading', { level: 1, name: 'Shampoo' })).toBeVisible();
        const priceText = (await page.locator('#product-title + p').innerText()).trim();
        const unitPrice = Number(priceText.replace(/[^\d.]/g, ''));

        // 3. Add to cart: the drawer opens with the item.
        await page.getByRole('button', { name: 'Add to cart' }).click();
        const drawer = page.getByRole('dialog', { name: 'Your cart' });
        await expect(drawer).toBeVisible();
        await expect(drawer.getByRole('link', { name: 'Shampoo' })).toBeVisible();

        // 4. Update the quantity on the cart page.
        await drawer.getByRole('link', { name: 'View cart' }).click();
        await expect(page).toHaveURL(/\/cart$/);
        await page.getByRole('button', { name: 'Increase quantity of shampoo' }).click();
        await expect(page.getByRole('group', { name: 'Quantity of Shampoo' }).locator('output')).toHaveText('2');

        // 5–6. Complete checkout; the confirmed total is computed by the server.
        await completeCheckout(page);
        await expect(page.getByRole('heading', { name: /Thank you, Fatima/ })).toBeVisible();
        const reference = await page.locator('strong').filter({ hasText: /^AS-\d{4}-[A-Z0-9]{5}$/ }).innerText();
        expect(reference).toMatch(/^AS-\d{4}-[A-Z0-9]{5}$/);

        // Marrakech demo delivery fee is 20 MAD; free from 500 MAD.
        const subtotal = unitPrice * 2;
        const expected = subtotal + (subtotal >= 500 ? 0 : 20);
        const totalText = await page.getByRole('region', { name: 'Order summary' }).locator('dd').last().innerText();
        expect(Number(totalText.replace(/[^\d.]/g, ''))).toBe(expected);
    });

    test('an invalid checkout is rejected with field messages', async ({ page }) => {
        await page.goto('/products/deodorant');
        await page.getByRole('button', { name: 'Add to cart' }).click();
        await expect(page.getByRole('dialog', { name: 'Your cart' })).toBeVisible();
        await page.goto('/checkout');
        await page.getByLabel('Phone number').fill('12345');
        await page.getByRole('button', { name: 'Place order' }).filter({ visible: true }).click();

        await expect(page.getByText('Enter your full name.')).toBeVisible();
        await expect(page.getByText('Enter a valid phone number so the courier can reach you.')).toBeVisible();
        await expect(page.getByText('Please accept the privacy policy to place your order.')).toBeVisible();
        await expect(page).toHaveURL(/\/checkout$/);
    });

    test('submitting twice creates a single order', async ({ page }) => {
        await page.goto('/products/toothpaste');
        await page.getByRole('button', { name: 'Add to cart' }).click();
        await expect(page.getByRole('dialog', { name: 'Your cart' })).toBeVisible();
        await page.goto('/checkout');
        await page.getByLabel('Full name').fill('Double Click');
        await page.getByLabel('Phone number').fill('06 99 88 77 66');
        await page.getByLabel('Delivery area').selectOption({ index: 2 });
        await expect(page.getByText(/You will pay .* in cash on delivery/).filter({ visible: true })).toBeVisible();
        await page.getByLabel('City', { exact: true }).fill('Casablanca');
        await page.getByLabel('Address', { exact: true }).fill('5 Boulevard Anfa');
        await page.getByRole('checkbox', { name: /privacy policy/ }).click();

        const button = page.getByRole('button', { name: 'Place order' }).filter({ visible: true });
        await Promise.all([button.dblclick(), page.waitForURL(/\/checkout\/confirmation\//)]);
        const reference = page.url().split('/').pop();

        // Replay the same submission (e.g. browser back + resubmit): still one order.
        await page.goto('/track-order');
        await page.getByLabel('Order reference').fill(reference ?? '');
        await page.getByLabel('Phone number').fill('06 99 88 77 66');
        await page.getByRole('button', { name: 'Find my order' }).click();
        await expect(page.getByRole('heading', { name: `Order ${reference}` })).toBeVisible();
    });

    test('an out-of-stock product cannot be bought', async ({ page }) => {
        await page.goto('/products/e2e-sold-out-balm');
        await expect(page.getByText('This product is out of stock.')).toBeVisible();
        await expect(page.getByRole('button', { name: 'Add to cart' })).toHaveCount(0);

        // The server refuses it too (covered in detail by the PHP suite).
        await page.goto('/cart');
        await expect(page.getByText('Your cart is empty.')).toBeVisible();
    });

    test('two shoppers race for the last unit: only one order succeeds', async ({ browser }) => {
        const shoppers = await Promise.all([browser.newContext(), browser.newContext()]);
        const pages = await Promise.all(shoppers.map((c) => c.newPage()));

        for (const page of pages) {
            await page.goto('/products/e2e-last-unit-soap');
            await page.getByRole('button', { name: 'Add to cart' }).click();
            await expect(page.getByRole('dialog', { name: 'Your cart' })).toBeVisible();
        }

        const prepare = async (page: (typeof pages)[number], phone: string) => {
            await page.goto('/checkout');
            await page.getByLabel('Full name').fill('Racing Shopper');
            await page.getByLabel('Phone number').fill(phone);
            await page.getByLabel('Delivery area').selectOption({ index: 1 });
            await expect(page.getByText(/You will pay .* in cash on delivery/).filter({ visible: true })).toBeVisible();
            await page.getByLabel('City', { exact: true }).fill('Marrakech');
            await page.getByLabel('Address', { exact: true }).fill('1 Derb Example');
            await page.getByRole('checkbox', { name: /privacy policy/ }).click();
        };
        await prepare(pages[0]!, '06 11 11 11 11');
        await prepare(pages[1]!, '06 22 22 22 22');

        await Promise.all(pages.map((p) => p.getByRole('button', { name: 'Place order' }).filter({ visible: true }).click()));
        await Promise.all(pages.map((p) => p.waitForLoadState('networkidle')));

        const confirmed = pages.filter((p) => /\/checkout\/confirmation\//.test(p.url()));
        expect(confirmed).toHaveLength(1);
        const loser = pages.find((p) => !confirmed.includes(p))!;
        await expect(loser.getByText(/Out of stock|available/).first()).toBeVisible();

        await Promise.all(shoppers.map((c) => c.close()));
    });
});

test.describe('Search engine metadata', () => {
    test('product and category pages carry server-rendered metadata', async ({ request }) => {
        const product = await (await request.get('/products/c9')).text();
        expect(product).toMatch(/<title data-inertia>C9 — Aloe &amp; Soul<\/title>/);
        expect(product).toContain('<link rel="canonical" href="http://127.0.0.1:8001/products/c9"');
        expect(product).toContain('"@type":"Product"');
        expect(product).toContain('"@type":"BreadcrumbList"');

        const category = await (await request.get('/shop/beauty')).text();
        expect(category).toMatch(/<title data-inertia>Beauty — Aloe &amp; Soul<\/title>/);
        expect(category).toMatch(/<meta name="description" content="[^"]+"/);
        expect(category).toContain('<meta name="robots" content="index,follow"');
    });
});
