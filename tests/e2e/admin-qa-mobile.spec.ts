// Author: MEMORA solutions, https://memora.solutions ; info@memora.ca
import { test, expect } from '@playwright/test';

test('Mobile QA — 375px regression check', async ({ page }) => {
  test.setTimeout(60000);
  await page.setViewportSize({ width: 375, height: 812 });

  // Login
  await page.goto('/login');
  await page.fill('#login-email', 'e2e-superadmin@test.local');
  await page.fill('#login-password', 'e2e-test-password');
  await page.click('form[wire\\:submit] button[type="submit"]');
  await page.waitForURL(/\/(admin|dashboard)/, { timeout: 15000 });

  const pages = [
    'admin',
    'admin/blog/articles',
    'admin/users',
    'admin/booking/dashboard',
    'admin/profile',
  ];

  for (const pagePath of pages) {
    const safename = pagePath.replace(/\//g, '_');

    const response = await page.goto(`/${pagePath}`, { waitUntil: 'networkidle', timeout: 20000 });
    expect(response?.status()).toBeLessThan(500);

    // Check no horizontal overflow
    const overflow = await page.evaluate(() => ({
      scrollW: document.documentElement.scrollWidth,
      clientW: document.documentElement.clientWidth,
    }));
    expect(overflow.scrollW, `Horizontal overflow on /${pagePath}`).toBeLessThanOrEqual(overflow.clientW + 5);

    await page.screenshot({ path: `./screenshots/qa/mobile_${safename}.png`, fullPage: true });
  }
});
