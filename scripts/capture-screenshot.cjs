var puppeteer = require('puppeteer-extra');
var StealthPlugin = require('puppeteer-extra-plugin-stealth');
var idcac = require('idcac-playwright');
var fs = require('fs');
var https = require('https');
var http = require('http');

puppeteer.use(StealthPlugin());

var url = process.argv[2];
var outputPath = process.argv[3];
var MIN_FILE_SIZE = 20 * 1024; // 20 KB minimum = screenshot valide

if (!url || !outputPath) {
    console.log(JSON.stringify({ success: false, error: 'Usage: node capture-screenshot.cjs URL OUTPUT_PATH' }));
    process.exit(0);
}

var COOKIE_HIDE = '.cookie-banner, .cookie-consent, #cookie-consent, .cc-window, .onetrust-banner-sdk, #CybotCookiebotDialog, [class*="cookie-banner"], [id*="cookie-banner"], #axeptio_overlay, .ax-widget-overlay, #didomi-notice, #didomi-popup, .didomi-popup-view, #tarteaucitronRoot, #tarteaucitronAlertBig, .qc-cmp2-container, #usercentrics-root, [class*="gdpr"], [class*="consent-banner"], [id*="gdpr"]';
var COOKIE_CLICK = ['.cookie-accept', '#cookie-consent button', '.cc-btn', '.cc-dismiss', '.onetrust-accept-btn-handler', '#accept-cookies', 'button[id*="accept"]', 'button[class*="accept"]', '.ax-button--primary', '[data-qa="accept-button"]', '#didomi-notice-agree-button', '#tarteaucitronPersonalize2', '#tarteaucitronAllAllowed', 'button[mode="primary"]', '[data-testid="uc-accept-all-button"]', '[aria-label*="accept"]', '[class*="agree"]'];

async function dismissCookies(page) {
    var hideStyle = COOKIE_HIDE + ' { display: none !important; visibility: hidden !important; opacity: 0 !important; }';
    var pass = async function () {
        try { await page.addStyleTag({ content: hideStyle }); } catch (e) {}
        try {
            await page.evaluate(function (selectors) {
                selectors.forEach(function (s) {
                    try { document.querySelectorAll(s).forEach(function (el) { el.click(); }); } catch (e) {}
                });
            }, COOKIE_CLICK);
        } catch (e) {}
    };
    await pass();
    await new Promise(function (r) { setTimeout(r, 1000); });
    await pass();
    try { await page.keyboard.press('Escape'); } catch (e) {}
    try {
        await page.evaluate(function (ts) {
            try { localStorage.setItem('axeptio_answers', '{}'); } catch (e) {}
            try { localStorage.setItem('didomi_token', 'accepted'); } catch (e) {}
            try { document.cookie = 'CookieConsent=yes; path=/'; } catch (e) {}
            try { document.cookie = 'OptanonAlertBoxClosed=' + ts + '; path=/'; } catch (e) {}
        }, Date.now());
    } catch (e) {}
}

// Detecter si la page est bloquee (Cloudflare, CAPTCHA, erreur)
var BLOCKED_TITLES = ['just a moment', 'attention required', 'access denied', 'security check'];
var BLOCKED_CONTENT = ['Performing security verification', 'Verify you are human', 'cf-turnstile', 'challenge-platform', 'ray ID', 'Enable JavaScript and cookies to continue', 'Checking your browser'];

function downloadFile(fileUrl, destPath) {
    return new Promise(function (resolve, reject) {
        var imgUrl = fileUrl.startsWith('//') ? 'https:' + fileUrl : fileUrl;
        var mod = imgUrl.startsWith('https') ? https : http;
        mod.get(imgUrl, { headers: { 'User-Agent': 'Mozilla/5.0' } }, function (res) {
            if (res.statusCode >= 300 && res.statusCode < 400 && res.headers.location) {
                // Follow redirect
                downloadFile(res.headers.location, destPath).then(resolve).catch(reject);
                return;
            }
            if (res.statusCode !== 200) { reject(new Error('HTTP ' + res.statusCode)); return; }
            var chunks = [];
            res.on('data', function (c) { chunks.push(c); });
            res.on('end', function () {
                var buf = Buffer.concat(chunks);
                fs.writeFileSync(destPath, buf);
                resolve(buf.length);
            });
        }).on('error', reject);
    });
}

(async function () {
    var browser = null;
    try {
        browser = await puppeteer.launch({
            headless: 'new',
            args: ['--no-sandbox', '--disable-setuid-sandbox', '--disable-dev-shm-usage', '--disable-gpu'],
            ignoreHTTPSErrors: true
        });

        var page = await browser.newPage();
        await page.setViewport({ width: 1200, height: 630 });

        // Stealth + idcac cookie dismiss before navigation
        await page.evaluateOnNewDocument(idcac.getInjectableScript());

        // Navigate (catch timeout gracefully)
        try {
            await page.goto(url, { timeout: 30000, waitUntil: 'networkidle2' });
        } catch (e) { /* continue with partial load */ }

        // Dismiss cookies (2 passes + ESC + localStorage pré-accept)
        await dismissCookies(page);

        await new Promise(function (r) { setTimeout(r, 1000); });

        // DETECTION : page bloquee?
        var blocked = false;
        try {
            var title = (await page.title() || '').toLowerCase();
            for (var i = 0; i < BLOCKED_TITLES.length; i++) {
                if (title.indexOf(BLOCKED_TITLES[i]) !== -1) { blocked = true; break; }
            }
        } catch (e) {}

        if (!blocked) {
            try {
                var htmlContent = await page.content();
                for (var j = 0; j < BLOCKED_CONTENT.length; j++) {
                    if (htmlContent.indexOf(BLOCKED_CONTENT[j]) !== -1) { blocked = true; break; }
                }
            } catch (e) {}
        }

        if (blocked) {
            // FALLBACK : og:image
            var ogImage = null;
            try {
                ogImage = await page.evaluate(function () {
                    var el = document.querySelector('meta[property="og:image"]');
                    return el ? el.getAttribute('content') : null;
                });
            } catch (e) {}

            await browser.close();
            browser = null;

            if (ogImage) {
                try {
                    var bytes = await downloadFile(ogImage, outputPath);
                    if (bytes >= MIN_FILE_SIZE) {
                        console.log(JSON.stringify({ success: true, path: outputPath, method: 'og:image', ogUrl: ogImage }));
                    } else {
                        console.log(JSON.stringify({ success: false, error: 'og:image trop petite (' + Math.round(bytes / 1024) + ' KB)', blocked: true, tooSmall: true }));
                    }
                } catch (dlErr) {
                    console.log(JSON.stringify({ success: false, error: 'Bloque + og:image download echoue: ' + dlErr.message, blocked: true }));
                }
            } else {
                console.log(JSON.stringify({ success: false, error: 'Page bloquee (Cloudflare/CAPTCHA), pas d og:image', blocked: true }));
            }
        } else {
            // CAPTURE NORMALE
            await page.screenshot({ path: outputPath, type: 'jpeg', quality: 85, fullPage: false });
            await browser.close();
            browser = null;

            // VALIDATION : taille fichier
            var fileSize = fs.statSync(outputPath).size;
            if (fileSize < MIN_FILE_SIZE) {
                console.log(JSON.stringify({ success: false, error: 'Screenshot trop petit (' + Math.round(fileSize / 1024) + ' KB)', tooSmall: true, path: outputPath }));
            } else {
                console.log(JSON.stringify({ success: true, path: outputPath, method: 'screenshot', size: fileSize }));
            }
        }
    } catch (error) {
        if (browser) { try { await browser.close(); } catch (e) {} }
        console.log(JSON.stringify({ success: false, error: error.message }));
    }
})();
