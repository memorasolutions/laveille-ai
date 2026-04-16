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

  console.log('Navigation vers webhooks...');
  await page.goto('https://dashboard.gelato.com/webhooks', { waitUntil: 'networkidle2', timeout: 60000 });
  await sleep(3000);

  // Si page de login, attendre
  if (page.url().includes('auth') || page.url().includes('login')) {
    console.log('>>> CONNECTE-TOI <<<');
    await page.waitForFunction(() => !window.location.href.includes('auth') && !window.location.href.includes('login'), { timeout: 300000 });
    await sleep(5000);
    await page.goto('https://dashboard.gelato.com/webhooks', { waitUntil: 'networkidle2', timeout: 30000 });
    await sleep(3000);
  }

  // === ÉTAPE 1 : Cliquer Add Webhook ===
  console.log('Clic sur Add Webhook...');
  await page.evaluate(() => {
    const btns = [...document.querySelectorAll('button, a')];
    const btn = btns.find(b => b.textContent.trim().toLowerCase().includes('add webhook'));
    if (btn) btn.click();
  });
  await sleep(3000);
  await shot(page, 'f1-form');

  // === ÉTAPE 2 : Remplir l'URL avec keyboard ===
  console.log('Remplissage URL...');
  // Cliquer sur le champ URL (premier input)
  const urlClicked = await page.evaluate(() => {
    const inputs = [...document.querySelectorAll('input')];
    // Le champ URL devrait être le premier input ou avoir un label "URL"
    for (const input of inputs) {
      const label = input.closest('div')?.querySelector('label')?.textContent || '';
      if (label.toLowerCase().includes('url') || inputs.indexOf(input) === 0) {
        input.focus();
        input.click();
        return label || 'first-input';
      }
    }
    return false;
  });
  console.log('  Input focus: ' + urlClicked);
  await sleep(500);

  // Taper l'URL au clavier
  await page.keyboard.type(WEBHOOK_URL, { delay: 30 });
  await sleep(1000);
  await shot(page, 'f2-url-typed');

  // === ÉTAPE 3 : Sélectionner les événements ===
  console.log('Sélection événements...');
  // Cliquer sur le dropdown Events
  const eventsClicked = await page.evaluate(() => {
    const labels = [...document.querySelectorAll('label, span, div')];
    const evtLabel = labels.find(l => l.textContent.trim().toLowerCase() === 'events');
    if (evtLabel) {
      // Chercher le select/dropdown le plus proche
      const parent = evtLabel.closest('div')?.parentElement;
      const select = parent?.querySelector('select, [role="combobox"], [role="listbox"], input, [class*="select"], [class*="dropdown"]');
      if (select) { select.click(); return 'select clicked'; }
      // Cliquer sur le div qui suit le label
      const nextDiv = evtLabel.nextElementSibling || evtLabel.parentElement?.querySelector('[class*="select"], [class*="control"]');
      if (nextDiv) { nextDiv.click(); return 'next div clicked'; }
    }
    return false;
  });
  console.log('  Events dropdown: ' + eventsClicked);
  await sleep(2000);
  await shot(page, 'f3-events-open');

  // Sélectionner les événements order_status_updated et order_item_tracking_code_updated
  const eventsSelected = await page.evaluate(() => {
    const options = [...document.querySelectorAll('[role="option"], [role="menuitem"], li, div[class*="option"]')];
    let selected = [];
    for (const opt of options) {
      const text = opt.textContent.toLowerCase();
      if (text.includes('order') || text.includes('status') || text.includes('tracking') || text.includes('all')) {
        opt.click();
        selected.push(opt.textContent.trim());
      }
    }
    return selected;
  });
  console.log('  Events sélectionnés:', eventsSelected);
  await sleep(1000);
  await shot(page, 'f4-events-selected');

  // Fermer le dropdown en cliquant ailleurs
  await page.click('body');
  await sleep(500);

  // === ÉTAPE 4 : Cliquer Create ===
  console.log('Clic sur Create...');
  const created = await page.evaluate(() => {
    const btns = [...document.querySelectorAll('button')];
    const btn = btns.find(b => b.textContent.trim().toLowerCase() === 'create');
    if (btn) { btn.click(); return true; }
    return false;
  });
  console.log('  Create cliqué: ' + created);
  await sleep(3000);
  await shot(page, 'f5-created');

  // === ÉTAPE 5 : Gelato+ trial ===
  console.log('\nNavigation Gelato+...');
  await page.evaluate(() => {
    const links = [...document.querySelectorAll('a, button')];
    const gp = links.find(l => {
      const text = l.textContent?.toLowerCase() || '';
      return text.includes('gelato+') || text.includes('try gelato') || (text.includes('start') && text.includes('free'));
    });
    if (gp) gp.click();
  });
  await sleep(3000);
  await shot(page, 'f6-gelatoplus');

  // Cliquer sur le bouton trial si visible
  const trialClicked = await page.evaluate(() => {
    const btns = [...document.querySelectorAll('button, a')];
    const btn = btns.find(b => {
      const text = b.textContent?.toLowerCase() || '';
      return text.includes('start') || text.includes('trial') || text.includes('free') || text.includes('try');
    });
    if (btn) { btn.click(); return btn.textContent.trim(); }
    return false;
  });
  console.log('  Trial cliqué: ' + trialClicked);
  await sleep(3000);
  await shot(page, 'f7-trial');

  console.log('\n=== TERMINÉ ===');
  console.log('Navigateur ouvert 5 min.');
  await new Promise(r => setTimeout(r, 300000));
  await browser.close();
})();
