

const { test, expect } = require('@playwright/test');

test.describe('Livewire Hook Test', () => {
  let browser, context, page;
  const consoleErrors = [];

  test.beforeAll(async ({ playwright }) => {
    browser = await playwright.chromium.launch({ headless: false });
    context = await browser.newContext({
      ignoreHTTPSErrors: true, // Important for local .test domains
    });
    page = await context.newPage();

    // Listen for console errors
    page.on('console', msg => {
      if (msg.type() === 'error') {
        consoleErrors.push(msg.text());
      }
    });
  });

  test.afterAll(async () => {
    await browser.close();
  });

  test('should test the livewire form submission hook', async () => {
    console.log('ACTION 2: Navigating to https://laravel_vierge.test/admin/settings?tab=general');
    await page.goto('https://laravel_vierge.test/admin/settings?tab=general');

    console.log('ACTION 3: Checking for login redirect.');
    if (page.url().includes('/login')) {
      console.log('Redirected to login. Logging in...');
      await page.fill('input[name="email"]', 'admin@example.com');
      await page.fill('input[name="password"]', 'password');
      await page.click('button[type="submit"]');
      await page.waitForURL('**/admin/settings?tab=general');
      console.log('Logged in successfully.');
    }

    console.log('ACTION 4: Taking initial screenshot: /tmp/hook-test-01-settings.png');
    await page.screenshot({ path: '/tmp/hook-test-01-settings.png' });

    console.log('ACTION 5: Inspecting DOM for buttons.');
    const buttons = await page.locator('button[wire\:click], button[type="submit"]').all();
    console.log(`Found ${buttons.length} buttons with wire:click or type=submit.`);

    console.log('ACTION 6: Executing JS to check Livewire.');
    const livewireStatus = await page.evaluate(() => {
      const isLoaded = typeof Livewire !== 'undefined';
      const hookInstalled = isLoaded && typeof Livewire.hook === 'function';
      console.log('Livewire:', isLoaded ? 'CHARGÉ' : 'NON CHARGÉ');
      console.log('Hook commit installé ?', hookInstalled ? 'OUI' : 'NON');
      return { isLoaded, hookInstalled };
    });

    console.log('ACTION 7: Ready to click. Console error listener is active.');

    const saveButtonSelector = 'form[wire\:submit\.prevent="save"] button[type="submit"]';
    const saveButton = page.locator(saveButtonSelector);
    
    await expect(saveButton).toBeVisible();
    console.log('ACTION 8: Clicking the "Sauvegarder" button.');
    await saveButton.click();

    console.log('ACTION 9: Checking if button is disabled immediately after click.');
    const isDisabled = await saveButton.isDisabled();
    console.log(`Button disabled status: ${isDisabled}`);

    console.log('ACTION 10: Taking loading screenshot: /tmp/hook-test-02-loading.png');
    await page.screenshot({ path: '/tmp/hook-test-02-loading.png' });
    
    const spinnerVisible = await saveButton.locator('span.spinner-border').isVisible();
    console.log(`Spinner visible: ${spinnerVisible}`);

    console.log('ACTION 11: Waiting for the request to complete.');
    // Wait for the toast message to appear as a sign of completion
    const toastSelector = '.toast.show, .alert-success'; 
    const toast = page.locator(toastSelector).first();
    await toast.waitFor({ state: 'visible', timeout: 5000 });

    console.log('ACTION 12: Taking screenshot after save: /tmp/hook-test-03-after-save.png');
    await page.screenshot({ path: '/tmp/hook-test-03-after-save.png' });

    console.log('ACTION 13: Verifying success toast.');
    const toastVisible = await toast.isVisible();
    const toastText = toastVisible ? await toast.textContent() : 'Not found';
    console.log(`Success toast visible: ${toastVisible}`);
    console.log(`Toast text: ${toastText.trim()}`);
    
    console.log('ACTION 14: Retrieving console errors.');
    console.log('JS Errors:', consoleErrors.length > 0 ? JSON.stringify(consoleErrors, null, 2) : 'None');

    // Final Report Generation
    console.log('\n--- FINAL REPORT ---');
    console.log(`Livewire est-il chargé ? ${livewireStatus.isLoaded ? 'oui' : 'non'}`);
    console.log(`Combien de boutons wire:click ou type=submit trouvés ? ${buttons.length}`);
    console.log(`Le bouton est-il devenu disabled pendant la requête ? ${isDisabled ? 'oui' : 'non'}`);
    console.log(`Un spinner s'est-il affiché ? ${spinnerVisible ? 'oui' : 'non'}`);
    console.log(`Le toast de succès s'est-il affiché ? ${toastVisible ? `oui, texte: "${toastText.trim()}"` : 'non'}`);
    console.log(`Y a-t-il des erreurs JS ? ${consoleErrors.length > 0 ? JSON.stringify(consoleErrors, null, 2) : 'non'}`);
    console.log('Screenshots pris :');
    console.log('- /tmp/hook-test-01-settings.png');
    console.log('- /tmp/hook-test-02-loading.png');
    console.log('- /tmp/hook-test-03-after-save.png');
    console.log('--- END OF REPORT ---');
  });
});
