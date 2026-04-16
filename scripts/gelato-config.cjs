const puppeteer = require('puppeteer-extra');
const StealthPlugin = require('puppeteer-extra-plugin-stealth');
puppeteer.use(StealthPlugin());
const fs = require('fs');

const WEBHOOK_URL = 'https://laveille.ai/webhooks/gelato';
const SCREENSHOTS = '/tmp/gelato';

async function screenshot(page, name) {
  fs.mkdirSync(SCREENSHOTS, { recursive: true });
  const path = `${SCREENSHOTS}/${name}.png`;
  await page.screenshot({ path, fullPage: true });
  console.log(`  Screenshot: ${path}`);
  return path;
}

async function waitAndClick(page, selectors, label) {
  for (const sel of selectors) {
    try {
      const el = await page.waitForSelector(sel, { timeout: 5000, visible: true });
      if (el) {
        await el.click();
        console.log(`  Cliqué: ${label} (${sel})`);
        await new Promise(r => setTimeout(r, 2000));
        return true;
      }
    } catch (e) { /* next selector */ }
  }
  console.log(`  Non trouvé: ${label}`);
  return false;
}

(async () => {
  const browser = await puppeteer.launch({
    headless: false,
    executablePath: '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome',
    args: ['--no-sandbox', '--start-maximized', '--disable-blink-features=AutomationControlled'],
    defaultViewport: null,
    userDataDir: '/tmp/gelato-chrome-profile'
  });

  const page = await browser.newPage();

  // === CONNEXION ===
  console.log('=== Navigation vers Gelato dashboard ===');
  await page.goto('https://dashboard.gelato.com', { waitUntil: 'networkidle2', timeout: 60000 });
  await new Promise(r => setTimeout(r, 3000));

  let url = page.url();
  console.log('URL actuelle: ' + url);

  if (url.includes('login') || url.includes('sign')) {
    console.log('>>> CONNECTE-TOI AU DASHBOARD GELATO <<<');
    await page.waitForFunction(() => {
      return !window.location.href.includes('login') && !window.location.href.includes('sign');
    }, { timeout: 300000 });
    console.log('Connexion détectée !');
    await new Promise(r => setTimeout(r, 5000));
  }

  await screenshot(page, '01-dashboard');

  // === ÉTAPE 1: WEBHOOKS ===
  console.log('\n=== ÉTAPE 1: Webhooks ===');
  try {
    await page.goto('https://dashboard.gelato.com/webhooks', { waitUntil: 'networkidle2', timeout: 30000 });
    await new Promise(r => setTimeout(r, 3000));
    await screenshot(page, '02-webhooks-page');

    // Chercher bouton Add/Create
    const added = await waitAndClick(page, [
      'button:has-text("Add")', 'button:has-text("Create")', 'button:has-text("Ajouter")',
      'a:has-text("Add")', 'a:has-text("Create")',
      '[data-testid*="add"]', '[data-testid*="create"]',
      '.btn-primary', 'button.primary'
    ], 'Add Webhook');

    if (added) {
      await screenshot(page, '03-webhook-form');

      // Remplir URL
      const inputs = await page.$$('input[type="text"], input[type="url"], input[placeholder*="url" i], input[placeholder*="endpoint" i], input[name*="url" i]');
      for (const input of inputs) {
        const placeholder = await input.evaluate(el => el.placeholder || el.name || '');
        console.log('  Input trouvé: ' + placeholder);
      }

      if (inputs.length > 0) {
        await inputs[0].click({ clickCount: 3 });
        await inputs[0].type(WEBHOOK_URL, { delay: 50 });
        console.log('  URL saisie: ' + WEBHOOK_URL);
      }

      await screenshot(page, '04-webhook-filled');
    }

    await screenshot(page, '05-webhooks-final');
  } catch (e) {
    console.log('Erreur webhooks: ' + e.message);
    await screenshot(page, '02-webhooks-error');
  }

  // === ÉTAPE 2: BILLING ===
  console.log('\n=== ÉTAPE 2: Billing / Wallet ===');
  try {
    await page.goto('https://dashboard.gelato.com/billing', { waitUntil: 'networkidle2', timeout: 30000 });
    await new Promise(r => setTimeout(r, 3000));
    await screenshot(page, '06-billing');

    // Chercher Wallets ou Auto top-up
    await waitAndClick(page, [
      'a:has-text("Wallet")', 'button:has-text("Wallet")',
      'a:has-text("Top")', 'button:has-text("Top")',
      '[href*="wallet"]'
    ], 'Wallet section');

    await new Promise(r => setTimeout(r, 2000));
    await screenshot(page, '07-wallet');
  } catch (e) {
    console.log('Erreur billing: ' + e.message);
    await screenshot(page, '06-billing-error');
  }

  // === ÉTAPE 3: GELATO+ ===
  console.log('\n=== ÉTAPE 3: Gelato+ ===');
  try {
    await page.goto('https://dashboard.gelato.com/subscription', { waitUntil: 'networkidle2', timeout: 30000 });
    await new Promise(r => setTimeout(r, 3000));
    await screenshot(page, '08-subscription');
  } catch (e) {
    console.log('Erreur subscription: ' + e.message);
    try {
      await page.goto('https://dashboard.gelato.com/gelato-plus', { waitUntil: 'networkidle2', timeout: 30000 });
      await new Promise(r => setTimeout(r, 3000));
      await screenshot(page, '08-gelatoplus');
    } catch (e2) {
      console.log('Erreur gelato+: ' + e2.message);
      await screenshot(page, '08-gelatoplus-error');
    }
  }

  console.log('\n=== TERMINÉ — Screenshots dans /tmp/gelato/ ===');
  console.log('Le navigateur reste ouvert 5 minutes.');
  await new Promise(r => setTimeout(r, 300000));
  await browser.close();
})();
