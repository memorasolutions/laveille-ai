const puppeteer = require('puppeteer-extra');
const StealthPlugin = require('puppeteer-extra-plugin-stealth');
puppeteer.use(StealthPlugin());
const fs = require('fs');

const WEBHOOK_URL = 'https://laveille.ai/webhooks/gelato';
const DIR = '/tmp/gelato';
const sleep = ms => new Promise(r => setTimeout(r, ms));

async function shot(page, name) {
  fs.mkdirSync(DIR, { recursive: true });
  await page.screenshot({ path: `${DIR}/${name}.png`, fullPage: true });
  console.log(`  >> ${name}.png`);
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
  console.log('Navigation vers Gelato...');
  await page.goto('https://dashboard.gelato.com', { waitUntil: 'networkidle2', timeout: 60000 });
  await sleep(3000);

  if (page.url().includes('auth') || page.url().includes('login')) {
    console.log('>>> CONNECTE-TOI, j\'attends... <<<');
    await page.waitForFunction(() => !window.location.href.includes('auth') && !window.location.href.includes('login'), { timeout: 300000 });
    await sleep(5000);
  }
  console.log('Connecté: ' + page.url());

  // === WEBHOOKS ===
  console.log('\n=== WEBHOOKS ===');
  // Naviguer via le menu Developer > Webhooks
  await page.goto('https://dashboard.gelato.com/webhooks', { waitUntil: 'networkidle2', timeout: 30000 });
  await sleep(3000);
  await shot(page, 'w1-page');

  // Trouver et cliquer "Add Webhook" par XPath text
  const clicked = await page.evaluate(() => {
    const buttons = [...document.querySelectorAll('button, a')];
    const btn = buttons.find(b => b.textContent.trim().toLowerCase().includes('add webhook'));
    if (btn) { btn.click(); return true; }
    // Fallback: chercher tout bouton dans la zone principale
    const primary = buttons.find(b => b.textContent.trim().toLowerCase().includes('add'));
    if (primary) { primary.click(); return true; }
    return false;
  });
  console.log('  Add Webhook cliqué: ' + clicked);
  await sleep(3000);
  await shot(page, 'w2-after-click');

  // Chercher les inputs du formulaire
  const formInfo = await page.evaluate(() => {
    const inputs = [...document.querySelectorAll('input, select, textarea')];
    return inputs.map(i => ({
      tag: i.tagName,
      type: i.type,
      name: i.name,
      placeholder: i.placeholder,
      id: i.id,
      className: i.className.substring(0, 50)
    }));
  });
  console.log('  Inputs trouvés:', JSON.stringify(formInfo, null, 2));

  // Remplir l'URL dans le premier input text/url
  const filled = await page.evaluate((url) => {
    const inputs = [...document.querySelectorAll('input[type="text"], input[type="url"], input:not([type])')];
    for (const input of inputs) {
      if (input.placeholder?.toLowerCase().includes('url') ||
          input.placeholder?.toLowerCase().includes('endpoint') ||
          input.name?.toLowerCase().includes('url') ||
          inputs.indexOf(input) === 0) {
        const nativeInputValueSetter = Object.getOwnPropertyDescriptor(window.HTMLInputElement.prototype, 'value').set;
        nativeInputValueSetter.call(input, url);
        input.dispatchEvent(new Event('input', { bubbles: true }));
        input.dispatchEvent(new Event('change', { bubbles: true }));
        return input.placeholder || input.name || 'first-input';
      }
    }
    return false;
  }, WEBHOOK_URL);
  console.log('  URL remplie dans: ' + filled);
  await sleep(2000);
  await shot(page, 'w3-url-filled');

  // Chercher les checkboxes d'événements
  const checkboxes = await page.evaluate(() => {
    const cbs = [...document.querySelectorAll('input[type="checkbox"], [role="checkbox"]')];
    return cbs.map(c => ({
      id: c.id,
      name: c.name,
      label: c.closest('label')?.textContent?.trim()?.substring(0, 60) || c.nextElementSibling?.textContent?.trim()?.substring(0, 60) || '',
      checked: c.checked
    }));
  });
  console.log('  Checkboxes:', JSON.stringify(checkboxes, null, 2));

  // Cocher les events qui nous intéressent
  await page.evaluate(() => {
    const cbs = [...document.querySelectorAll('input[type="checkbox"], [role="checkbox"]')];
    for (const cb of cbs) {
      const label = (cb.closest('label')?.textContent || cb.nextElementSibling?.textContent || '').toLowerCase();
      if (label.includes('order') || label.includes('status') || label.includes('tracking') || label.includes('all')) {
        if (!cb.checked) {
          cb.click();
        }
      }
    }
  });
  await sleep(1000);
  await shot(page, 'w4-events-checked');

  // Chercher le bouton Save/Submit
  const saved = await page.evaluate(() => {
    const buttons = [...document.querySelectorAll('button, input[type="submit"]')];
    const btn = buttons.find(b => {
      const text = b.textContent?.trim().toLowerCase() || '';
      return text.includes('save') || text.includes('create') || text.includes('submit') || text.includes('add');
    });
    if (btn) { btn.click(); return btn.textContent.trim(); }
    return false;
  });
  console.log('  Bouton sauvegarde cliqué: ' + saved);
  await sleep(3000);
  await shot(page, 'w5-saved');

  // === BILLING / WALLET ===
  console.log('\n=== BILLING ===');
  // Naviguer via le menu sidebar
  const billingUrl = await page.evaluate(() => {
    const links = [...document.querySelectorAll('a')];
    const billing = links.find(l => l.textContent.toLowerCase().includes('billing') || l.href?.includes('billing'));
    return billing?.href || null;
  });

  if (billingUrl) {
    await page.goto(billingUrl, { waitUntil: 'networkidle2', timeout: 30000 });
  } else {
    // Essayer les URLs possibles
    for (const url of ['https://dashboard.gelato.com/billing', 'https://dashboard.gelato.com/settings/billing']) {
      try {
        await page.goto(url, { waitUntil: 'networkidle2', timeout: 15000 });
        if (!page.url().includes('dashboard.gelato.com/dashboard')) break;
      } catch(e) {}
    }
  }
  await sleep(3000);
  await shot(page, 'b1-billing');

  // === GELATO+ ===
  console.log('\n=== GELATO+ ===');
  // Chercher dans la sidebar
  const gelatoPlusClicked = await page.evaluate(() => {
    const links = [...document.querySelectorAll('a, button')];
    const gp = links.find(l => {
      const text = l.textContent?.toLowerCase() || '';
      return text.includes('gelato+') || text.includes('gelato plus') || text.includes('try gelato') || text.includes('start') && text.includes('free');
    });
    if (gp) { gp.click(); return gp.textContent.trim(); }
    return false;
  });
  console.log('  Gelato+ cliqué: ' + gelatoPlusClicked);
  await sleep(3000);
  await shot(page, 'g1-gelatoplus');

  console.log('\n=== TERMINÉ ===');
  console.log('Navigateur ouvert 5 min pour vérification manuelle.');
  await new Promise(r => setTimeout(r, 300000));
  await browser.close();
})();
