const puppeteer = require('puppeteer');
const idcac = require('idcac-playwright');

const url = process.argv[2];
const outputPath = process.argv[3];

if (!url || !outputPath) {
    console.log(JSON.stringify({ success: false, error: 'Missing URL or output path arguments' }));
    process.exit(0);
}

(async () => {
    let browser = null;
    try {
        browser = await puppeteer.launch({
            headless: 'new',
            args: [
                '--no-sandbox',
                '--disable-setuid-sandbox',
                '--disable-dev-shm-usage',
                '--disable-gpu'
            ]
        });

        const page = await browser.newPage();
        await page.setViewport({ width: 1200, height: 630 });

        // Inject IDCAC script before navigation (dismisses 95% of cookie banners)
        const idcacScript = idcac.getInjectableScript();
        await page.evaluateOnNewDocument(idcacScript);

        // Navigate
        try {
            await page.goto(url, { timeout: 30000, waitUntil: 'networkidle2' });
        } catch (e) {
            // Continue even if timeout - page may be partially loaded
        }

        // CSS hide residual cookie banners
        var hideSelectors = [
            '.cookie-banner', '.cookie-consent', '#cookie-consent',
            '.cc-window', '.onetrust-banner-sdk', '#CybotCookiebotDialog',
            '[class*="cookie-banner"]', '[id*="cookie-banner"]'
        ];

        try {
            await page.addStyleTag({
                content: hideSelectors.join(', ') + ' { display: none !important; visibility: hidden !important; }'
            });
        } catch (e) {}

        // Fallback: click common accept buttons
        try {
            await page.evaluate(function () {
                var selectors = [
                    '.cookie-accept', '#cookie-consent button', '.cc-btn',
                    '.cc-dismiss', '.onetrust-accept-btn-handler', '#accept-cookies',
                    'button[id*="accept"]', 'button[class*="accept"]'
                ];
                selectors.forEach(function (s) {
                    try { document.querySelectorAll(s).forEach(function (el) { el.click(); }); } catch (e) {}
                });
            });
        } catch (e) {}

        // Wait for dismiss animations
        await new Promise(function (resolve) { setTimeout(resolve, 1500); });

        // Capture
        await page.screenshot({
            path: outputPath,
            type: 'jpeg',
            quality: 85,
            fullPage: false
        });

        await browser.close();
        console.log(JSON.stringify({ success: true, path: outputPath }));

    } catch (error) {
        if (browser) { try { await browser.close(); } catch (e) {} }
        console.log(JSON.stringify({ success: false, error: error.message }));
    }
})();
