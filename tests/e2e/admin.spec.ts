import { expect, test } from '@playwright/test';

import { completeCheckout, signIn } from './fixtures';

test.describe('Administration', () => {
    test('visitors cannot reach admin pages or actions', async ({ page, request }) => {
        await page.goto('/admin/orders');
        await expect(page).toHaveURL(/\/admin\/login$/);

        const response = await request.post('/admin/products', { data: { name: 'Hack' }, maxRedirects: 0 });
        expect([302, 419]).toContain(response.status());
    });

    test('an administrator updates a product price and the shop shows it', async ({ page }) => {
        await signIn(page);
        await page.goto('/admin/products?q=Bee+Propolis');
        await page.getByRole('link', { name: 'Bee Propolis' }).click();
        await expect(page.getByRole('heading', { level: 1, name: 'Bee Propolis' })).toBeVisible();

        await page.getByLabel('Price', { exact: true }).fill('199.50');
        await page.getByRole('button', { name: 'Save product' }).click();
        await expect(page.getByText('Product saved.')).toBeVisible();

        await page.goto('/products/bee-propolis');
        await expect(page.locator('#product-title + p')).toContainText('199.50 MAD');
    });

    test('an administrator processes an order through valid steps', async ({ page, browser }) => {
        const shopper = await browser.newContext();
        const shop = await shopper.newPage();
        await shop.goto('/products/aloe-body-wash');
        await shop.getByRole('button', { name: 'Add to cart' }).click();
        await expect(shop.getByRole('dialog', { name: 'Your cart' })).toBeVisible();
        const reference = await completeCheckout(shop, { name: 'Order Flow', phone: '06 55 44 33 22' });
        await shopper.close();

        await signIn(page);
        await page.goto(`/admin/orders/${reference}`);
        await expect(page.getByRole('heading', { name: `Order ${reference}` })).toBeVisible();

        // Only valid next steps are offered.
        await expect(page.getByRole('button', { name: 'Mark as delivered' })).toHaveCount(0);

        for (const action of ['Confirm order', 'Start preparing', 'Mark as packed', 'Hand to courier']) {
            await page.getByRole('button', { name: action }).click();
            await page.getByRole('dialog').getByRole('button', { name: action }).click();
            await expect(page.getByRole('dialog')).toHaveCount(0);
        }

        await page.getByRole('button', { name: 'Mark as delivered' }).click();
        await page.getByRole('dialog').getByRole('button', { name: 'Mark as delivered' }).click();
        await expect(page.getByText('Cash collected').first()).toBeVisible();
        await expect(page.getByText('Delivered').first()).toBeVisible();
    });
});
