import { test, expect } from '@playwright/test';

test.describe('Authentication pages', () => {
  test('login page loads with h1 and form', async ({ page }) => {
    await page.goto('/login');

    await expect(page.locator('h1')).toBeVisible();
    await expect(page.locator('form[wire\\:submit]')).toBeVisible();
    await expect(page.locator('#login-email')).toBeVisible();
    await expect(page.locator('#login-password')).toBeVisible();
    await expect(page.locator('.auth-btn')).toBeVisible();
  });

  test('login with wrong password shows error', async ({ page }) => {
    await page.goto('/login');

    await page.fill('#login-email', 'wrong@example.com');
    await page.fill('#login-password', 'wrongpassword');
    await page.click('.auth-btn');

    // Livewire reloads - wait for validation error
    await expect(page.locator('.auth-error')).toBeVisible({ timeout: 10000 });
  });

  test('register page loads with form fields', async ({ page }) => {
    await page.goto('/register');

    await expect(page.locator('h1')).toBeVisible();
    await expect(page.locator('form[wire\\:submit]')).toBeVisible();
    await expect(page.locator('#register-name')).toBeVisible();
    await expect(page.locator('#register-email')).toBeVisible();
    await expect(page.locator('#register-password')).toBeVisible();
    await expect(page.locator('#register-password-confirm')).toBeVisible();
  });

  test('forgot password page loads', async ({ page }) => {
    await page.goto('/forgot-password');

    await expect(page.locator('h1')).toBeVisible();
    await expect(page.locator('form[wire\\:submit]')).toBeVisible();
    await expect(page.locator('#forgot-email')).toBeVisible();
    await expect(page.locator('.auth-btn')).toBeVisible();
  });

  test('login page has password toggle button', async ({ page }) => {
    await page.goto('/login');

    const toggleBtn = page.locator('.toggle-password-btn');
    await expect(toggleBtn).toBeVisible();

    // Click toggle - password field should switch to text
    await toggleBtn.click();
    await expect(page.locator('#login-password')).toHaveAttribute('type', 'text');

    // Click again - back to password
    await toggleBtn.click();
    await expect(page.locator('#login-password')).toHaveAttribute('type', 'password');
  });
});
