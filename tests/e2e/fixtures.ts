import { expect, type Page } from '@playwright/test';

/** Test-only credentials created by database/seeders/E2eSeeder.php. */
export const OWNER = { email: 'e2e-owner@example.test', password: 'E2e-Owner-Pass-2026' };

export const CUSTOMER = {
    name: 'Fatima Zahra',
    phone: '06 12 34 56 78',
    city: 'Marrakech',
    address: '12 Rue de la Kasbah, Medina',
};

/** Fill and submit checkout. Returns the confirmation reference. */
export async function completeCheckout(page: Page, overrides: Partial<typeof CUSTOMER> = {}): Promise<string> {
    const c = { ...CUSTOMER, ...overrides };
    await page.goto('/checkout');
    await page.getByLabel('Full name').fill(c.name);
    await page.getByLabel('Phone number').fill(c.phone);
    await page.getByLabel('Delivery area').selectOption({ index: 1 }); // Marrakech (demo zone)
    await expect(page.getByText(/You will pay .* in cash on delivery/).filter({ visible: true })).toBeVisible();
    await page.getByLabel('City', { exact: true }).fill(c.city);
    await page.getByLabel('Address', { exact: true }).fill(c.address);
    await page.getByRole('checkbox', { name: /privacy policy/ }).click();
    await page.getByRole('button', { name: 'Place order' }).filter({ visible: true }).click();
    await expect(page).toHaveURL(/\/checkout\/confirmation\//);
    return page.url().split('/').pop() ?? '';
}

export async function signIn(page: Page) {
    await page.goto('/admin/login');
    await page.getByLabel('Email', { exact: true }).fill(OWNER.email);
    await page.getByLabel('Password', { exact: true }).fill(OWNER.password);
    await page.getByRole('button', { name: 'Sign in' }).click();
    await expect(page).toHaveURL(/\/admin$/);
}
