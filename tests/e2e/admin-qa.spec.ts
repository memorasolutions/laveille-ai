// Author: MEMORA solutions, https://memora.solutions ; info@memora.ca
import { test, expect } from '@playwright/test';
import * as fs from 'fs';
import * as path from 'path';

const SCREENSHOT_DIR = path.join(process.cwd(), 'screenshots', 'qa');

const ADMIN_PAGES = [
  'admin',
  'admin/blog/articles',
  'admin/blog/categories',
  'admin/blog/tags',
  'admin/blog/comments',
  'admin/pages',
  'admin/newsletter/campaigns',
  'admin/newsletter/templates',
  'admin/newsletter/workflows',
  'admin/users',
  'admin/roles',
  'admin/teams',
  'admin/plans',
  'admin/revenue',
  'admin/booking/dashboard',
  'admin/booking/appointments',
  'admin/booking/services',
  'admin/booking/calendar',
  'admin/settings',
  'admin/feature-flags',
  'admin/seo',
  'admin/redirects',
  'admin/shortcodes',
  'admin/webhooks',
  'admin/translations',
  'admin/activity-logs',
  'admin/mail-log',
  'admin/login-history',
  'admin/failed-jobs',
  'admin/backups',
  'admin/cache',
  'admin/health',
  'admin/system-info',
  'admin/scheduler',
  'admin/blocked-ips',
  'admin/security',
  'admin/data-retention',
  'admin/trash',
  'admin/media',
  'admin/ai/conversations',
  'admin/ai/analytics',
  'admin/ai/knowledge',
  'admin/profile',
  'admin/notifications',
  'admin/announcements',
  'admin/testimonials',
  'admin/faqs',
  'admin/formbuilder/forms',
  'admin/widgets',
  'admin/menus',
  'admin/custom-fields',
  'admin/short-urls',
  'admin/ecommerce',
  'admin/ecommerce/products',
  'admin/ecommerce/orders',
  'admin/ecommerce/coupons',
  'admin/storage',
  'admin/stats',
  'admin/tenants',
  'admin/plugins',
  'admin/onboarding-steps',
  'admin/push-notifications',
  'admin/cookie-categories',
  'admin/email-templates',
];

test.describe('Admin pages QA — batch screenshot', () => {
  test('login and visit all admin pages', async ({ page }) => {
    test.setTimeout(300000); // 5 min for 65 pages

    fs.mkdirSync(SCREENSHOT_DIR, { recursive: true });

    // Login
    await page.goto('/login');
    await page.fill('#login-email', 'e2e-superadmin@test.local');
    await page.fill('#login-password', 'e2e-test-password');
    await page.click('form[wire\\:submit] button[type="submit"]');
    await page.waitForURL(/\/(admin|dashboard)/, { timeout: 15000 });

    const results: { page: string; status: number | null; error?: string }[] = [];

    for (const pagePath of ADMIN_PAGES) {
      try {
        const response = await page.goto(`/${pagePath}`, { waitUntil: 'networkidle', timeout: 20000 });
        const status = response?.status() ?? 0;

        // Screenshot
        const safeName = pagePath.replace(/\//g, '_');
        await page.screenshot({ path: path.join(SCREENSHOT_DIR, `${safeName}.png`), fullPage: true });

        if (status >= 500) {
          results.push({ page: pagePath, status, error: 'HTTP 500+' });
        } else if (status >= 400) {
          results.push({ page: pagePath, status, error: 'HTTP 4xx' });
        } else {
          // Check for content
          const hasContent = await page.locator('.card, table, .table-responsive, form, .container-fluid').first().isVisible().catch(() => false);
          if (!hasContent) {
            results.push({ page: pagePath, status, error: 'No content visible' });
          } else {
            results.push({ page: pagePath, status });
          }
        }
      } catch (e: unknown) {
        const msg = e instanceof Error ? e.message : String(e);
        results.push({ page: pagePath, status: null, error: msg.substring(0, 100) });

        // Screenshot even on error
        const safeName = pagePath.replace(/\//g, '_');
        await page.screenshot({ path: path.join(SCREENSHOT_DIR, `${safeName}_error.png`), fullPage: true }).catch(() => {});
      }
    }

    // Write report
    const report = results.map(r =>
      `${r.error ? 'FAIL' : 'OK  '} | ${r.status ?? '???'} | /${r.page}${r.error ? ` — ${r.error}` : ''}`
    ).join('\n');

    fs.writeFileSync(path.join(SCREENSHOT_DIR, 'REPORT.txt'), report);

    // Assert no 500 errors
    const errors500 = results.filter(r => (r.status ?? 0) >= 500);
    expect(errors500, `Pages with 500 errors: ${errors500.map(r => r.page).join(', ')}`).toHaveLength(0);

    // Log summary
    const okCount = results.filter(r => !r.error).length;
    const failCount = results.filter(r => r.error).length;
    console.log(`\nQA Report: ${okCount} OK, ${failCount} issues out of ${results.length} pages`);
    if (failCount > 0) {
      console.log('Issues:');
      results.filter(r => r.error).forEach(r => console.log(`  /${r.page}: ${r.error}`));
    }
  });
});
