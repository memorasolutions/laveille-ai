// Author: MEMORA solutions, https://memora.solutions ; info@memora.ca
import { test, expect, type Page } from '@playwright/test';

// #P2-lien-enorme (2026-08-30) : le bouton "Partager mon calcul" doit produire un lien qui ne
// transporte QUE l'état du calcul (province, montant, pourboire), jamais les paramètres de la
// page de départ (utm_*, fbclid, gclid...). Régression mesurée en navigateur réel avant correctif :
// 61 caractères sur un atterrissage propre contre 233 avec des paramètres de traçage réalistes sur
// la page de départ - buildShareUrl() clonait alors window.location.href en entier. Corrigé en
// repartant toujours de window.location.origin + window.location.pathname.

async function refuserCookies(page: Page): Promise<void> {
    const refuse = page.locator('#cc-btn-refuse');
    if (await refuse.count()) {
        await refuse.click().catch(() => {});
    }
}

async function intercepterPartage(page: Page): Promise<void> {
    await page.addInitScript(() => {
        (window as unknown as { __shareCaptures: unknown[] }).__shareCaptures = [];
        Object.defineProperty(navigator, 'share', {
            value: (data: unknown) => {
                (window as unknown as { __shareCaptures: unknown[] }).__shareCaptures.push(data);
                return Promise.resolve();
            },
            configurable: true,
        });
        Object.defineProperty(navigator, 'canShare', { value: () => true, configurable: true });
    });
}

async function remplirEtPartager(page: Page): Promise<string> {
    await page.selectOption('#province', 'QC');
    await page.locator('#amount-before-tax').fill('100');
    await page.locator('#amount-before-tax').dispatchEvent('input');
    await page.locator('#ct-tip-toggle-btn').click();
    await page.locator('.rt-tip-preset[data-tip="15"]').click();
    await page.waitForTimeout(300);
    await page.locator('#share-calc-btn').click();
    await page.waitForTimeout(500);

    const captures = await page.evaluate(
        () => (window as unknown as { __shareCaptures: Array<{ url: string }> }).__shareCaptures || []
    );
    expect(captures.length).toBeGreaterThan(0);
    return captures[0].url;
}

test.describe('Calculatrice de taxes - lien de partage', () => {
    test('un atterrissage propre produit un lien minimal (3 paramètres, sous 100 caractères)', async ({ page }) => {
        await intercepterPartage(page);
        await page.goto('/outils/calculatrice-taxes');
        await refuserCookies(page);

        const shareUrl = await remplirEtPartager(page);

        expect(shareUrl).toContain('p=QC');
        expect(shareUrl).toContain('a=100');
        expect(shareUrl).toContain('t=15');
        expect(shareUrl.length).toBeLessThan(100);
    });

    test('les paramètres de traçage de la page de départ (utm_*, fbclid) ne se retrouvent jamais dans le lien partagé', async ({ page }) => {
        await intercepterPartage(page);
        const atterrissagePollue =
            '/outils/calculatrice-taxes?utm_source=facebook&utm_medium=social' +
            '&utm_campaign=campagne-ete-2026-calculatrice-taxes-quebec' +
            '&fbclid=IwAR2b9x7K3mNQpL8vRt5wYcZaJhF1oXsU4dEgHiKjMnOqPrStUvWxYz0123456789abc';
        await page.goto(atterrissagePollue);
        await refuserCookies(page);

        const shareUrl = await remplirEtPartager(page);

        expect(shareUrl).not.toContain('utm_');
        expect(shareUrl).not.toContain('fbclid');
        expect(shareUrl).toContain('p=QC');
        expect(shareUrl).toContain('a=100');
        expect(shareUrl.length).toBeLessThan(100);
    });

    test('un lien de partage connu (p=QC&a=100&t=15) reconstitue le calcul à l\'identique', async ({ page }) => {
        // Vérifie le côté LECTURE (initFromUrl) indépendamment du côté ÉCRITURE testé plus haut :
        // une seule navigation, directement vers l'URL que produit buildShareUrl() pour Québec /
        // 100 $ / pourboire 15 % (valeur figée ici plutôt que rechaînée après un premier partage
        // dans le même test - une seconde navigation sur la même page s'est avérée non fiable dans
        // CE harnais précis, `php artisan serve` mono-thread + Debugbar local, la page se fermant
        // seule après le second chargement sans rapport avec le correctif testé ici). Le même
        // round-trip, rejoué en navigateur réel contre laveilledestef.test (Herd) et contre la
        // production laveille.ai, est prouvé séparément dans le rapport de livraison.
        await page.goto('/outils/calculatrice-taxes?p=QC&a=100&t=15');
        await refuserCookies(page);
        await page.waitForTimeout(700);

        await expect(page.locator('#province')).toHaveValue('QC');
        await expect(page.locator('#amount-before-tax')).toHaveValue('100');
        await expect(page.locator('#amount-after-tax')).toHaveValue('114,98');
        await expect(page.locator('#rt-result-tip-amount')).toContainText('15,00');
        await expect(page.locator('#rt-result-final')).toContainText('129,98');
    });
});
