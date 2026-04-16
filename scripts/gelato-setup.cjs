const puppeteer = require('puppeteer-extra');
const StealthPlugin = require('puppeteer-extra-plugin-stealth');
puppeteer.use(StealthPlugin());

const WEBHOOK_URL = 'https://laveille.ai/webhooks/gelato';

(async () => {
  const browser = await puppeteer.launch({
    headless: false,
    args: ['--no-sandbox', '--start-maximized', '--disable-blink-features=AutomationControlled'],
    defaultViewport: null
  });

  const page = await browser.newPage();
  await page.goto('https://dashboard.gelato.com', { waitUntil: 'networkidle2', timeout: 60000 });

  console.log('=== EN ATTENTE DE CONNEXION ===');
  console.log('Connecte-toi au dashboard Gelato, puis la page devrait rediriger vers le dashboard.');

  // Attendre que l'utilisateur soit connecté (URL contient /dashboard ou /orders ou pas /login)
  await page.waitForFunction(() => {
    return !window.location.href.includes('/login') && !window.location.href.includes('/sign');
  }, { timeout: 300000 }); // 5 minutes max

  console.log('Connecté ! Navigation vers les webhooks...');
  await page.waitForTimeout(3000);

  // === ÉTAPE 1 : WEBHOOKS ===
  try {
    console.log('\n=== ÉTAPE 1 : Configuration webhook ===');
    await page.goto('https://dashboard.gelato.com/webhooks', { waitUntil: 'networkidle2', timeout: 30000 });
    await page.waitForTimeout(3000);

    // Capture screenshot
    await page.screenshot({ path: '/tmp/gelato-webhooks.png', fullPage: true });
    console.log('Screenshot webhooks sauvegardé : /tmp/gelato-webhooks.png');

    // Chercher le bouton "Add webhook" ou similaire
    const addButton = await page.$('button:has-text("Add"), button:has-text("Create"), a:has-text("Add"), a:has-text("Create")');
    if (addButton) {
      await addButton.click();
      await page.waitForTimeout(2000);

      // Remplir l'URL
      const urlInput = await page.$('input[type="url"], input[placeholder*="url" i], input[placeholder*="endpoint" i], input[name*="url" i]');
      if (urlInput) {
        await urlInput.click({ clickCount: 3 });
        await urlInput.type(WEBHOOK_URL);
        console.log('URL webhook remplie : ' + WEBHOOK_URL);
      }

      await page.screenshot({ path: '/tmp/gelato-webhook-form.png', fullPage: true });
      console.log('Screenshot formulaire webhook sauvegardé');
    } else {
      console.log('Bouton "Add webhook" non trouvé — capture page pour diagnostic');
    }
  } catch (e) {
    console.log('Erreur webhooks : ' + e.message);
    await page.screenshot({ path: '/tmp/gelato-webhooks-error.png', fullPage: true });
  }

  // === ÉTAPE 2 : BILLING / WALLET ===
  try {
    console.log('\n=== ÉTAPE 2 : Wallet auto top-up ===');
    await page.goto('https://dashboard.gelato.com/billing', { waitUntil: 'networkidle2', timeout: 30000 });
    await page.waitForTimeout(3000);
    await page.screenshot({ path: '/tmp/gelato-billing.png', fullPage: true });
    console.log('Screenshot billing sauvegardé : /tmp/gelato-billing.png');
  } catch (e) {
    console.log('Erreur billing : ' + e.message);
  }

  // === ÉTAPE 3 : GELATO+ TRIAL ===
  try {
    console.log('\n=== ÉTAPE 3 : Gelato+ trial ===');
    await page.goto('https://dashboard.gelato.com/subscription', { waitUntil: 'networkidle2', timeout: 30000 });
    await page.waitForTimeout(3000);
    await page.screenshot({ path: '/tmp/gelato-subscription.png', fullPage: true });
    console.log('Screenshot subscription sauvegardé : /tmp/gelato-subscription.png');
  } catch (e) {
    console.log('Erreur subscription : ' + e.message);
  }

  console.log('\n=== SCREENSHOTS PRÊTS — en attente 5 minutes ===');
  console.log('Le navigateur reste ouvert pour compléter manuellement si nécessaire.');
  await new Promise(r => setTimeout(r, 300000));
  await browser.close();
})();
