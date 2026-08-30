// Author: MEMORA solutions, https://memora.solutions ; info@memora.ca
import { test, expect, type Page } from '@playwright/test';

// Chantier accessibilité des composants PARTAGÉS signalés (mais délibérément non corrigés)
// pendant l'audit P0 de la calculatrice de taxes, 2026-08-30 :
//
// 1) Bandeau infolettre (fronttheme::partials.tools-newsletter-cta, via le composant
//    Modules/FrontTheme/resources/views/components/newsletter-form.blade.php) : le placeholder
//    de l'input courriel n'avait jamais de couleur explicite - il héritait du gris par défaut du
//    navigateur (#757575 sur Chromium), mesuré à 4,61:1 sur fond blanc (mcp__wcag-mcp__wcag_check_contrast) :
//    passe l'AA (4,5:1) mais échoue l'AAA (7:1) visé par le projet. Corrigé par
//    `.lv-newsletter-email::placeholder { color: var(--sys-text-muted-aaa, #4b5563); opacity: 1; }`
//    -> 7,56:1 sur blanc, 7,22:1 sur --sys-surface-raised (#F8FAFB). 20 fichiers Blade utilisent ce
//    composant (dont les 15 fiches d'outils, l'index /outils et la page lien-expiré via
//    tools-newsletter-cta), tous corrigés d'un seul coup par ce composant partagé.
//
// 2) Bouton d'aide (.ct-help-btn, public/css/charte.css, "définition UNIQUE" documentée dans le
//    code) : le cercle visuel (22-24px) est sous la cible tactile AAA de 44px, compensé par une
//    zone de clic invisible (::after) - mais cette zone était un CERCLE de 44px de DIAMÈTRE
//    (aire ≈1521px², sous les 1936px² d'un carré 44×44), pas un vrai carré. Mesuré par clic réel :
//    le coin de la zone nominale sur calculatrice-taxes.blade.php retombait sur le <select>
//    voisin plutôt que d'ouvrir la modale d'aide. Corrigé en retirant le border-radius:50% du
//    ::after (carré plein 44×44, cercle visuel inchangé).

async function refuserCookies(page: Page): Promise<void> {
    const refuse = page.locator('#cc-btn-refuse');
    if (await refuse.count()) {
        await refuse.click().catch(() => {});
    }
}

test.describe('Composants partagés - bandeau infolettre et bouton d\'aide (accessibilité AAA)', () => {
    test('le placeholder de l\'input courriel du bandeau infolettre respecte le contraste AAA (7:1)', async ({ page }) => {
        await page.goto('/outils/calculatrice-taxes');
        await refuserCookies(page);

        const email = page.locator('.lv-newsletter-email');
        await expect(email).toBeVisible();
        await expect(email).toHaveAttribute('placeholder', /./);

        const style = await email.evaluate((el) => {
            const cs = getComputedStyle(el, '::placeholder');
            return { color: cs.color, opacity: cs.opacity };
        });

        // #4b5563 = rgb(75, 85, 99) - seule couleur qui passe l'AAA (7:1) à la fois sur blanc
        // (7,56:1) et sur --sys-surface-raised #F8FAFB (7,22:1), mesuré mcp__wcag-mcp__wcag_check_contrast.
        expect(style.color).toBe('rgb(75, 85, 99)');
        expect(style.opacity).toBe('1');
    });

    test('un clic dans le coin de la zone tactile 44×44 du bouton d\'aide ouvre la modale (pas le contrôle voisin)', async ({ page }) => {
        await page.goto('/outils/calculatrice-taxes');
        await refuserCookies(page);

        const helpBtn = page.locator('.ct-help-btn[data-help-key="province"]');
        await expect(helpBtn).toBeVisible();
        const box = await helpBtn.boundingBox();
        expect(box).not.toBeNull();
        if (!box) return;

        const cx = box.x + box.width / 2;
        const cy = box.y + box.height / 2;
        // Décalage diagonal de 21px : à l'intérieur d'un carré 44×44 (demi-côté 22) mais à
        // l'extérieur d'un cercle inscrit de 44px de diamètre (rayon 22, distance diagonale
        // 21*racine(2) ≈ 29,7 > 22) - le point précis qui distingue les deux formes.
        const offset = 21;

        const overlay = page.locator('.ct-modal-overlay');
        await expect(overlay).toBeHidden();

        await page.mouse.click(cx - offset, cy - offset);
        await expect(overlay).toBeVisible();
        await expect(page.locator('#ct-help-modal-title')).toBeVisible();
    });

    test('un clic hors de la zone 44×44 (30px du centre) n\'ouvre pas la modale d\'aide', async ({ page }) => {
        // Garde-fou anti-régression inverse : la zone corrigée doit rester bornée à 44×44, pas
        // grandir au-delà (ce qui capturerait des clics destinés à des contrôles voisins).
        await page.goto('/outils/calculatrice-taxes');
        await refuserCookies(page);

        const helpBtn = page.locator('.ct-help-btn[data-help-key="province"]');
        const box = await helpBtn.boundingBox();
        expect(box).not.toBeNull();
        if (!box) return;

        const cx = box.x + box.width / 2;
        const cy = box.y + box.height / 2;

        await page.mouse.click(cx + 30, cy);
        await expect(page.locator('.ct-modal-overlay')).toBeHidden();
    });
});
