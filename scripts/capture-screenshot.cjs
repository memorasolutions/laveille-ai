var puppeteer = require('puppeteer-extra');
var StealthPlugin = require('puppeteer-extra-plugin-stealth');
var idcac = require('idcac-playwright');
var fs = require('fs');
var https = require('https');
var http = require('http');

puppeteer.use(StealthPlugin());

var url = process.argv[2];
var outputPath = process.argv[3];
var MIN_FILE_SIZE = 20 * 1024; // 20 KB minimum screenshot Puppeteer
var MIN_OG_SIZE = 5 * 1024;     // 5 KB minimum og:image (logos petits OK)

if (!url || !outputPath) {
    console.log(JSON.stringify({ success: false, error: 'Usage: node capture-screenshot.cjs URL OUTPUT_PATH' }));
    process.exit(0);
}

var COOKIE_HIDE = '.cookie-banner, .cookie-consent, #cookie-consent, .cc-window, .onetrust-banner-sdk, #CybotCookiebotDialog, [class*="cookie-banner"], [id*="cookie-banner"], #axeptio_overlay, .ax-widget-overlay, #didomi-notice, #didomi-popup, .didomi-popup-view, #tarteaucitronRoot, #tarteaucitronAlertBig, .qc-cmp2-container, #usercentrics-root, [class*="gdpr"], [class*="consent-banner"], [id*="gdpr"]';
var COOKIE_CLICK = ['.cookie-accept', '#cookie-consent button', '.cc-btn', '.cc-dismiss', '.onetrust-accept-btn-handler', '#accept-cookies', 'button[id*="accept"]', 'button[class*="accept"]', '.ax-button--primary', '[data-qa="accept-button"]', '#didomi-notice-agree-button', '#tarteaucitronPersonalize2', '#tarteaucitronAllAllowed', 'button[mode="primary"]', '[data-testid="uc-accept-all-button"]', '[aria-label*="accept"]', '[class*="agree"]'];

// Patterns 2026 : newsletter modals, chat widgets, promo overlays, generic modals
var POPUP_HIDE = '.newsletter-modal, #email-signup, [class*="newsletter"][class*="modal"], #intercom-container, .intercom-lightweight-app, [class*="intercom"], .klaviyo-form, [class*="klaviyo"], .optinmonster, [class*="optinmonster"], [id*="drift"], [class*="drift-frame"], iframe[src*="intercom"], iframe[src*="drift.com"], iframe[src*="tawk.to"], iframe[src*="crisp"], .popup-overlay, .modal-backdrop, .mailerlite-popup, [class*="privy"], [class*="sumome"], #hellobar, .hellobar, [role="dialog"][aria-modal="true"]:not([class*="cookie"]):not([id*="cookie"]), dialog[open]';
var POPUP_DISMISS = 'button[class*="close"], [aria-label*="close" i], [aria-label*="fermer" i], [aria-label*="dismiss" i], .modal-close, [class*="modal"] [class*="close"], button[class*="dismiss"], .popup-close, button[data-dismiss], [class*="reject"], [class*="no-thanks"], button[aria-label*="no thanks" i]';

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

// Dismiss popups 2026 : newsletter, chat widgets, promo modals (click + remove iframes)
async function dismissPopups(page) {
    try { await page.addStyleTag({ content: POPUP_HIDE + ' { display:none!important;visibility:hidden!important;pointer-events:none!important; }' }); } catch (e) {}
    try {
        await page.evaluate(function (dismissSel) {
            try {
                document.querySelectorAll(dismissSel).forEach(function (el) {
                    try { el.click(); } catch (e) {}
                });
            } catch (e) {}
            try {
                document.querySelectorAll('iframe[src*="intercom"], iframe[src*="drift"], iframe[src*="tawk"], iframe[src*="crisp"]').forEach(function (el) { el.remove(); });
            } catch (e) {}
        }, POPUP_DISMISS);
    } catch (e) {}
}

// Dismiss cookie banners par TEXTE du bouton (React/Next.js styled-components sans class stable)
async function dismissByText(page) {
    try {
        return await page.evaluate(function () {
            var patterns = [
                'accept all', 'accept', 'agree', 'allow all', 'allow',
                'accepter', 'tout accepter', "j'accepte", "d'accord",
                'got it', 'ok', 'compris', 'continuer',
                'save preferences', 'enregistrer', 'confirmer mon choix'
            ];
            var contextWords = /cookie|consent|privacy|rgpd|gdpr/i;
            var candidates = document.querySelectorAll('button, a, [role="button"]');
            var clicked = 0;
            for (var i = 0; i < candidates.length; i++) {
                try {
                    var el = candidates[i];
                    var rect = el.getBoundingClientRect();
                    if (rect.width < 20 || rect.height < 10) continue;
                    var style = window.getComputedStyle(el);
                    if (style.display === 'none' || style.visibility === 'hidden' || style.opacity === '0') continue;
                    var txt = (el.textContent || '').replace(/\s+/g, ' ').trim().toLowerCase();
                    var matched = false;
                    for (var p = 0; p < patterns.length; p++) {
                        if (txt.indexOf(patterns[p]) !== -1) { matched = true; break; }
                    }
                    if (!matched) continue;
                    var valid = false;
                    var ancestor = el;
                    for (var lvl = 0; lvl < 10 && ancestor && ancestor !== document.body; lvl++) {
                        var aStyle = window.getComputedStyle(ancestor);
                        if (aStyle.position === 'fixed' || aStyle.position === 'sticky') { valid = true; break; }
                        if (ancestor.getAttribute('role') === 'dialog' || ancestor.getAttribute('aria-modal') === 'true') { valid = true; break; }
                        var aTxt = (ancestor.textContent || '').toLowerCase();
                        if (contextWords.test(aTxt)) { valid = true; break; }
                        ancestor = ancestor.parentElement;
                    }
                    if (!valid) continue;
                    el.click();
                    clicked++;
                } catch (e) { /* skip */ }
            }
            return clicked;
        });
    } catch (e) { return 0; }
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

        // Auto-remove popups lazy-loaded 3s après window.load (patterns 2026)
        await page.evaluateOnNewDocument(function () {
            window.addEventListener('load', function () {
                setTimeout(function () {
                    try {
                        document.querySelectorAll('[class*="popup"], [class*="modal"]:not([class*="cookie"])').forEach(function (el) {
                            el.style.display = 'none';
                            el.style.visibility = 'hidden';
                        });
                    } catch (e) {}
                }, 3000);
            });
        });

        // Navigate (catch timeout gracefully)
        try {
            await page.goto(url, { timeout: 30000, waitUntil: 'networkidle2' });
        } catch (e) { /* continue with partial load */ }

        // Dismiss cookies (2 passes + ESC + localStorage pré-accept)
        await dismissCookies(page);

        // Wait 5s pour popups déclenchés 2-5s après load (BP 2026 #C timing)
        await new Promise(function (r) { setTimeout(r, 5000); });

        // Dismiss popups + text-based cookie banners + retry 3x (BP 2026 #A+#C + React styled-components)
        await dismissPopups(page);
        await dismissByText(page);
        for (var _retry = 0; _retry < 3; _retry++) {
            await new Promise(function (r) { setTimeout(r, 1500); });
            await dismissCookies(page);
            await dismissPopups(page);
            await dismissByText(page);
        }

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
                    if (bytes >= MIN_OG_SIZE) {
                        console.log(JSON.stringify({ success: true, path: outputPath, method: 'og:image', size: bytes, ogUrl: ogImage }));
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

            // VALIDATION : taille fichier
            var fileSize = fs.statSync(outputPath).size;
            if (fileSize < MIN_FILE_SIZE) {
                // Screenshot trop petit → tenter og:image fallback avant de fermer le browser
                var ogImage = null;
                try {
                    ogImage = await page.evaluate(function () {
                        var el = document.querySelector('meta[property="og:image"]');
                        return el ? el.getAttribute('content') : null;
                    });
                } catch (e) {
                    ogImage = null;
                }
                await browser.close();
                browser = null;

                var ogSuccess = false;
                if (ogImage) {
                    try {
                        await downloadFile(ogImage, outputPath);
                        var ogSize = fs.statSync(outputPath).size;
                        if (ogSize >= MIN_OG_SIZE) {
                            ogSuccess = true;
                            console.log(JSON.stringify({ success: true, path: outputPath, method: 'og:image', size: ogSize, ogUrl: ogImage }));
                        }
                    } catch (e) {
                        ogSuccess = false;
                    }
                }
                if (!ogSuccess) {
                    console.log(JSON.stringify({ success: false, error: 'Screenshot trop petit (' + Math.round(fileSize / 1024) + ' KB)', tooSmall: true, path: outputPath }));
                }
            } else {
                await browser.close();
                browser = null;
                console.log(JSON.stringify({ success: true, path: outputPath, method: 'screenshot', size: fileSize }));
            }
        }
    } catch (error) {
        if (browser) { try { await browser.close(); } catch (e) {} }
        console.log(JSON.stringify({ success: false, error: error.message }));
    }
})();
