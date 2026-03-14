// Author: MEMORA solutions, https://memora.solutions ; info@memora.ca
import { test, expect } from '@playwright/test';

test.describe('Public pages', () => {
  test('homepage loads', async ({ page }) => {
    await page.goto('/');
    await expect(page).toHaveTitle(/.+/);
    await expect(page.locator('body')).toBeVisible();
  });

  test('homepage has navigation', async ({ page }) => {
    await page.goto('/');
    await expect(page.locator('nav')).toBeVisible();
  });

  test('blog page loads', async ({ page }) => {
    await page.goto('/blog');
    await expect(page.locator('h1, h2')).toBeVisible();
  });

  test('FAQ page loads', async ({ page }) => {
    await page.goto('/faq');
    await expect(page.locator('h1, h2')).toBeVisible();
  });

  test('contact page loads with form fields', async ({ page }) => {
    await page.goto('/contact');
    await expect(page.getByText('Nom complet')).toBeVisible();
    await expect(page.getByText('Courriel')).toBeVisible();
    await expect(page.getByLabel('Message')).toBeVisible();
  });
});
