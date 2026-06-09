import { expect, test } from '@playwright/test';

test('shows the welcome page', async ({ page }) => {
    await page.goto('/');

    await expect(page).toHaveTitle(/Money Manager/);
});

