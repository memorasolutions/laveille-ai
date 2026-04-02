// scripts/extract-og-image.cjs
// Extrait og:image via Puppeteer stealth pour les sites SPA/anti-bot
// Usage: node extract-og-image.cjs <URL>
// Retourne l'URL og:image sur stdout, exit 0 si trouvé, 1 sinon

const puppeteer = require('puppeteer-extra');
const StealthPlugin = require('puppeteer-extra-plugin-stealth');

puppeteer.use(StealthPlugin());

(async () => {
  const url = process.argv[2];
  if (!url) {
    console.error('Usage: node extract-og-image.cjs <URL>');
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

    // Attendre 2 secondes pour laisser le JS s'executer
    await new Promise(resolve => setTimeout(resolve, 2000));

    const ogImage = await page.evaluate(() => {
      const og = document.querySelector('meta[property="og:image"]')?.content;
      if (og) return og;

      const twitter = document.querySelector('meta[name="twitter:image"]')?.content
        || document.querySelector('meta[property="twitter:image"]')?.content;
      return twitter || null;
    });

    await browser.close();

    if (ogImage) {
      console.log(ogImage);
      process.exit(0);
    } else {
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
