import { test, expect, Page } from '@playwright/test';

const E2E_PASSWORD = 'e2e-test-password';

/**
 * Helper to log in via the login page.
 * Test users are created by global-setup.ts → seed-e2e-users.php.
 */
async function loginAs(page: Page, email: string) {
  await page.goto('/login');
  await page.fill('#login-email', email);
  await page.fill('#login-password', E2E_PASSWORD);
  await page.click('.auth-btn');
  // Wait for Livewire redirect to user dashboard
  await page.waitForURL('**/dashboard', { timeout: 15000 });
}

test.describe('Admin RBAC - sidebar visibility', () => {
  /**
   * Sidebar items are inside Bootstrap collapse menus.
   * @can directives remove items from the DOM entirely.
   * We use toBeAttached() (in DOM) instead of toBeVisible() (on screen).
   */

  test('super_admin sees Rôles link in sidebar DOM', async ({ page }) => {
    await loginAs(page, 'e2e-superadmin@test.local');
    await page.goto('/admin');

    // Super admin should have Rôles link in the sidebar DOM
    await expect(page.locator('nav a:has-text("Rôles")')).toBeAttached();
    // And also Utilisateurs section
    await expect(page.locator('nav a:has-text("Utilisateurs")')).toBeAttached();
  });

  test('admin does NOT see Rôles link in sidebar', async ({ page }) => {
    await loginAs(page, 'e2e-admin@test.local');
    await page.goto('/admin');

    // Admin should NOT have Rôles link (removed by @can)
    await expect(page.locator('nav a:has-text("Rôles")')).not.toBeAttached();
    // But should have Utilisateurs
    await expect(page.locator('nav a:has-text("Utilisateurs")')).toBeAttached();
  });

  test('editor sees Articles but NOT Utilisateurs', async ({ page }) => {
    await loginAs(page, 'e2e-editor@test.local');
    await page.goto('/admin');

    // Editor should have Articles link (content section)
    await expect(page.locator('nav a[href*="articles"]')).toBeAttached();
    // But NOT Utilisateurs section (removed by @canany)
    await expect(page.locator('nav a:has-text("Utilisateurs")')).not.toBeAttached();
  });

  test('user role gets 403 on admin', async ({ page }) => {
    await loginAs(page, 'e2e-user@test.local');

    const response = await page.goto('/admin');
    expect(response?.status()).toBe(403);
  });

  test('guest is redirected to login', async ({ page }) => {
    await page.goto('/admin');
    await expect(page).toHaveURL(/\/login/);
  });
});
