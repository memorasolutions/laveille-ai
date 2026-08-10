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

// ACTION: CSS anti-instabilite injectee avant capture (design doc 2026-08-10, brique 2)
// SELF: neutralise animations/transitions/curseur clignotant qui font "trembler" les mesures de
// stabilite du DOM et le rendu final capture.
// RAISON IMPORTANTE - volontairement SANS "transform: none" global : un transform en !important
// casserait les mises en page qui positionnent des elements par transform (carrousels, sticky
// headers via translate3d, etc.) - risque de regression visuelle plus grand que le benefice de
// stabilite. NE JAMAIS l'ajouter sans discussion prealable (voir design doc, brique 2).
async function injectStabilityCss(page) {
    try {
        await page.addStyleTag({
            content: '* { animation: none !important; transition: none !important; caret-color: transparent !important; }',
        });
    } catch (e) { /* non bloquant */ }
}

// Attente bornee des polices (brique 2) : evite une capture avec police de fallback qui decale
// les hauteurs de texte au moment du screenshot. Bornee a 3s pour ne jamais bloquer indefiniment
// sur un site qui charge des polices lentement.
async function waitForFontsReady(page) {
    try {
        await Promise.race([
            page.evaluate(function () {
                if (!document.fonts || !document.fonts.ready) { return true; }
                return document.fonts.ready.then(function () { return true; });
            }),
            new Promise(function (resolve) { setTimeout(resolve, 3000); }),
        ]);
    } catch (e) { /* non bloquant */ }
}

// Mesure de stabilite du rendu (brique 2) : hauteur du DOM + progression du chargement des images.
async function measureLayoutMetrics(page) {
    try {
        return await page.evaluate(function () {
            var imgs = document.images || [];
            var complete = 0;
            for (var i = 0; i < imgs.length; i++) {
                if (imgs[i].complete) { complete++; }
            }
            return {
                height: document.body ? document.body.scrollHeight : 0,
                imageCount: imgs.length,
                completeImages: complete,
            };
        });
    } catch (e) {
        return { height: -1, imageCount: -1, completeImages: -1 };
    }
}

// Masquage geometrique generique (brique 2) : attrape les bandeaux plein ecran non couverts par
// les selecteurs codes en dur (COOKIE_HIDE/POPUP_HIDE) - fixed/sticky touchant un bord du
// viewport, couvrant plus de 20% de sa surface, avec z-index >= 100. Seuil de 20% choisi pour ne
// JAMAIS masquer un header de hero legitime (souvent < 15% de la surface d'un viewport 1200x1400).
// ACTION: budget de temps explicite + exclusion header/hero legitime (revue adversariale post-livraison)
// SELF: querySelectorAll('body *') + getComputedStyle par element est non borne sur un DOM enorme ;
// un header+hero sticky de 300px z-index 999 contenant un h1/nav serait sinon masque a tort.
// RAISON: budget ~1500ms verifie periodiquement dans la boucle (cout de Date.now() amorti tous les
// 200 elements) ; exclusion deterministe et simple (h1 ou nav avec texte substantiel a l'interieur).
async function hideFullscreenOverlays(page) {
    try {
        await page.evaluate(function () {
            try {
                var vw = window.innerWidth;
                var vh = window.innerHeight;
                var viewportArea = vw * vh;
                if (viewportArea <= 0) { return; }
                var budgetMs = 1500;
                var deadline = Date.now() + budgetMs;
                var els = document.querySelectorAll('body *');
                for (var i = 0; i < els.length; i++) {
                    if (i % 200 === 0 && Date.now() > deadline) { break; } // budget de temps atteint, on arrete proprement
                    var el = els[i];
                    try {
                        var style = window.getComputedStyle(el);
                        if (style.position !== 'fixed' && style.position !== 'sticky') { continue; }
                        var zIndex = parseInt(style.zIndex, 10);
                        if (isNaN(zIndex) || zIndex < 100) { continue; }
                        var rect = el.getBoundingClientRect();
                        if (rect.width <= 0 || rect.height <= 0) { continue; }
                        var touchesEdge = rect.top <= 0 || rect.bottom >= vh;
                        if (!touchesEdge) { continue; }
                        var area = rect.width * rect.height;
                        if (area / viewportArea <= 0.2) { continue; }
                        // Exclusion header/hero legitime : jamais masquer un element qui CONTIENT un
                        // h1 ou un nav avec du texte substantiel (ex. header+hero sticky 300px z-index 999).
                        var h1 = el.querySelector('h1');
                        var hasSubstantialH1 = !!h1 && (h1.textContent || '').trim().length >= 3;
                        var nav = el.querySelector('nav');
                        var hasSubstantialNav = !!nav && (nav.textContent || '').trim().length >= 10;
                        if (hasSubstantialH1 || hasSubstantialNav) { continue; }
                        el.style.setProperty('visibility', 'hidden', 'important');
                    } catch (elErr) { /* skip cet element */ }
                }
            } catch (evalErr) { /* non bloquant */ }
        });
    } catch (e) { /* non bloquant */ }
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
        // Viewport plus haut (1200x1400) + clip zone haute 630 : popups cookies bottom-banner exclues (idée 2026)
        await page.setViewport({ width: 1200, height: 1400 });

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

        // Navigate (catch timeout gracefully) - le statut reel est desormais logue explicitement
        // (brique 2) au lieu d'etre avale silencieusement par le try/catch.
        var gotoStatus = 'loaded';
        try {
            await page.goto(url, { timeout: 30000, waitUntil: 'networkidle2' });
        } catch (e) { gotoStatus = 'timeout-partial'; /* continue with partial load */ }

        await injectStabilityCss(page);
        await waitForFontsReady(page);

        // Dismiss cookies (2 passes + ESC + localStorage pré-accept)
        await dismissCookies(page);

        // Attente de stabilite bornee (brique 2) : remplace les sleeps fixes 5000ms + 3x1500ms
        // (budget total 9500ms conserve a l'identique, jamais depasse) par une mesure repetee de
        // la hauteur du DOM + des images chargees, espacee de 700ms. Deux mesures identiques
        // consecutives = rendu stable = capture anticipee (gain de temps). Sinon, la cascade de
        // dismiss continue jusqu'au delai maximal d'aujourd'hui (aucune lenteur ajoutee).
        var STABILITY_BUDGET_MS = 9500;
        var STABILITY_STEP_MS = 700;
        var stabilityDeadline = Date.now() + STABILITY_BUDGET_MS;
        var prevMetrics = null;
        for (;;) {
            await dismissPopups(page);
            await dismissByText(page);
            await dismissCookies(page);
            var metrics = await measureLayoutMetrics(page);
            if (prevMetrics && metrics.height === prevMetrics.height
                && metrics.imageCount === prevMetrics.imageCount
                && metrics.completeImages === prevMetrics.completeImages) {
                break; // deux mesures stables espacees de STABILITY_STEP_MS : capture anticipee
            }
            prevMetrics = metrics;
            if (Date.now() + STABILITY_STEP_MS >= stabilityDeadline) {
                break; // budget bientot epuise : ne pas attendre au-dela du delai maximal actuel
            }
            await new Promise(function (r) { setTimeout(r, STABILITY_STEP_MS); });
        }

        // Masquage geometrique generique (brique 2), apres la cascade de dismiss ci-dessus.
        await hideFullscreenOverlays(page);

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
            // Statut goto_status ecrase par le diagnostic de blocage (plus specifique que
            // loaded/timeout-partial - brique 2).
            gotoStatus = 'blocked';

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
                        console.log(JSON.stringify({ success: true, path: outputPath, method: 'og:image', size: bytes, ogUrl: ogImage, goto_status: gotoStatus }));
                    } else {
                        console.log(JSON.stringify({ success: false, error: 'og:image trop petite (' + Math.round(bytes / 1024) + ' KB)', blocked: true, tooSmall: true, goto_status: gotoStatus }));
                    }
                } catch (dlErr) {
                    console.log(JSON.stringify({ success: false, error: 'Bloque + og:image download echoue: ' + dlErr.message, blocked: true, goto_status: gotoStatus }));
                }
            } else {
                console.log(JSON.stringify({ success: false, error: 'Page bloquee (Cloudflare/CAPTCHA), pas d og:image', blocked: true, goto_status: gotoStatus }));
            }
        } else {
            // CAPTURE NORMALE
            // Master (brique 1) : capture complete du viewport 1200x1400, temp path derive du
            // outputPath - PHP se charge du deplacement atomique final vers
            // public/screenshots/masters/{slug}.jpg (design doc section 3, brique 1).
            var masterTempPath = outputPath.replace(/\.jpg$/i, '') + '.master.jpg';
            var masterWritten = false;
            try {
                await page.screenshot({ path: masterTempPath, type: 'jpeg', quality: 85, fullPage: false, clip: { x: 0, y: 0, width: 1200, height: 1400 } });
                masterWritten = true;
            } catch (masterErr) { masterWritten = false; }

            await page.screenshot({ path: outputPath, type: 'jpeg', quality: 85, fullPage: false, clip: { x: 0, y: 0, width: 1200, height: 630 } });

            // VALIDATION : taille fichier
            var fileSize = fs.statSync(outputPath).size;
            if (fileSize < MIN_FILE_SIZE) {
                // Pas de master pour un fallback og:image (brique 3) : nettoyage du temp master.
                if (masterWritten) { try { fs.unlinkSync(masterTempPath); } catch (cleanupErr) {} }
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
                            console.log(JSON.stringify({ success: true, path: outputPath, method: 'og:image', size: ogSize, ogUrl: ogImage, goto_status: gotoStatus }));
                        }
                    } catch (e) {
                        ogSuccess = false;
                    }
                }
                if (!ogSuccess) {
                    console.log(JSON.stringify({ success: false, error: 'Screenshot trop petit (' + Math.round(fileSize / 1024) + ' KB)', tooSmall: true, path: outputPath, goto_status: gotoStatus }));
                }
            } else {
                await browser.close();
                browser = null;
                var normalOutput = { success: true, path: outputPath, method: 'screenshot', size: fileSize, goto_status: gotoStatus };
                if (masterWritten) { normalOutput.master_path = masterTempPath; }
                console.log(JSON.stringify(normalOutput));
            }
        }
    } catch (error) {
        if (browser) { try { await browser.close(); } catch (e) {} }
        console.log(JSON.stringify({ success: false, error: error.message }));
    }
})();
