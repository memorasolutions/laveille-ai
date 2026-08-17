// scripts/extract-article.cjs
// Repli Puppeteer stealth pour Modules\News\Services\SourceMarkdownFetcher : rend la page
// (sites SPA/anti-bot que la requête HTTP simple ne peut pas lire) et sort le HTML complet du
// document sur stdout. Le parse Readability + la conversion Markdown restent côté PHP - ce
// script ne fait QUE rendre et retourner le HTML, calqué sur extract-og-image.cjs (mêmes
// conventions : puppeteer-extra + stealth, même user-agent, mêmes en-têtes de langue).
// Usage: node extract-article.cjs <URL>
// Retourne le HTML complet sur stdout, exit 0 si succès, 1 sinon (message sur stderr).

const puppeteer = require('puppeteer-extra');
const StealthPlugin = require('puppeteer-extra-plugin-stealth');

puppeteer.use(StealthPlugin());

(async () => {
  const url = process.argv[2];
  if (!url) {
    console.error('Usage: node extract-article.cjs <URL>');
    process.exit(1);
  }

  let browser;
  try {
    browser = await puppeteer.launch({
      headless: true,
      args: ['--no-sandbox', '--disable-setuid-sandbox'],
    });

    const page = await browser.newPage();

    await page.setUserAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36');
    await page.setExtraHTTPHeaders({
      'Accept-Language': 'fr-CA,fr;q=0.9,en-US;q=0.8,en;q=0.7',
    });

    await page.goto(url, {
      waitUntil: 'domcontentloaded',
      timeout: 15000,
    });

    // Attendre 2 secondes pour laisser le JS s'executer (meme delai que extract-og-image.cjs).
    await new Promise(resolve => setTimeout(resolve, 2000));

    const html = await page.content();

    await browser.close();

    if (html && html.trim().length > 0) {
      console.log(html);
      process.exit(0);
    } else {
      console.error('Page vide.');
      process.exit(1);
    }
  } catch (error) {
    console.error(error.message);
    if (browser) {
      await browser.close().catch(() => {});
    }
    process.exit(1);
  }
})();
