// tests/js/focal-cropper-math.test.cjs
// Garde-fou de non-régression du moteur de calcul PUR du composant x-core::focal-cropper
// (design doc 2026-08-10, recadrage frontend - CA-6). Zéro DOM, testable directement via Node.
// Exécuter : node tests/js/focal-cropper-math.test.cjs (ou npm run test:js)
const fs = require('fs');
const path = require('path');
// package.json a "type": "module" -> require() traiterait ce .js comme un ESM (module namespace
// figé, jamais nos exports). Même contournement que tests/js/prompt-verifier-rules-detect.test.cjs :
// évaluer la source directement via new Function('module', src).
const src = fs.readFileSync(path.join(__dirname, '../../public/assets/directory/focal-cropper-math.js'), 'utf8');
const _mod = { exports: {} };
new Function('module', src)(_mod);
const FocalCropperMath = _mod.exports;

let pass = 0, fail = 0;
function assert(cond, label) { if (cond) { pass++; console.log('  ✅ ' + label); } else { fail++; console.log('  ❌ ' + label); } }

// --- normalizedMasterHeight : jamais < 630, jamais > 1400, jamais un 770 en dur ---
assert(FocalCropperMath.normalizedMasterHeight(1400) === 1400, 'normalise 1400 -> 1400 (borne haute exacte)');
assert(FocalCropperMath.normalizedMasterHeight(2000) === 1400, 'normalise 2000 -> 1400 (jamais > 1400)');
assert(FocalCropperMath.normalizedMasterHeight(630) === 630, 'normalise 630 -> 630 (borne basse exacte)');
assert(FocalCropperMath.normalizedMasterHeight(400) === 630, 'normalise 400 -> 630 (jamais < 630)');
assert(FocalCropperMath.normalizedMasterHeight(900) === 900, 'normalise 900 -> 900 (valeur intermédiaire inchangée)');

// --- maxFocal : dérivé de la hauteur RÉELLE, jamais une constante partagée type 770 ---
assert(FocalCropperMath.maxFocal(1400) === 770, 'maxFocal(1400) = 770 (cas historique de l\'UI admin)');
assert(FocalCropperMath.maxFocal(900) === 270, 'maxFocal(900) = 270 (dépend de la hauteur réelle)');
assert(FocalCropperMath.maxFocal(630) === 0, 'maxFocal(630) = 0 (aucune marge de recadrage)');
assert(FocalCropperMath.maxFocal(2000) === 770, 'maxFocal(2000) plafonne comme maxFocal(1400) (defensif)');

// --- clampFocal : bornage 0..maxFocal, jamais rejeté ---
assert(FocalCropperMath.clampFocal(-500, 1400) === 0, 'clampFocal borne une valeur négative à 0');
assert(FocalCropperMath.clampFocal(999999, 1400) === 770, 'clampFocal borne une valeur trop grande au max réel');
assert(FocalCropperMath.clampFocal(400, 1400) === 400, 'clampFocal laisse passer une valeur déjà valide');
assert(FocalCropperMath.clampFocal(150, 630) === 0, 'clampFocal borne à 0 quand le master ne laisse aucune marge');
assert(FocalCropperMath.clampFocal(NaN, 1400) === 0, 'clampFocal traite NaN comme 0 (jamais NaN propagé)');

// --- pointerDeltaToFocalDelta : glisser vers le haut (delta écran négatif) fait DESCENDRE le focal ---
assert(FocalCropperMath.pointerDeltaToFocalDelta(-50, 1) === 50, 'glisser vers le haut (delta -50, échelle 1) augmente le focal de 50');
assert(FocalCropperMath.pointerDeltaToFocalDelta(50, 1) === -50, 'glisser vers le bas (delta +50, échelle 1) diminue le focal de 50');
assert(FocalCropperMath.pointerDeltaToFocalDelta(50, 0) === 0, 'échelle 0 (cadre non mesuré) ne produit aucun NaN/Infinity');
assert(FocalCropperMath.pointerDeltaToFocalDelta(-100, 2) === 50, 'échelle 2 (cadre affiché 2x plus grand que le master) divise le delta master par 2');

// --- focalTopPercent / focalBottomPercent : cohérence du clip-path (top% + bande 630/h*100% + bottom% = 100%) ---
(function () {
    const h = 1400;
    const focal = 300;
    const top = FocalCropperMath.focalTopPercent(focal, h);
    const bottom = FocalCropperMath.focalBottomPercent(focal, h);
    const bandPercent = (630 / h) * 100;
    assert(Math.abs(top + bandPercent + bottom - 100) < 0.001, 'top% + bande% + bottom% = 100% (aucun trou/chevauchement dans le clip-path)');
    assert(Math.abs(top - (300 / 1400) * 100) < 0.001, 'focalTopPercent proportionnel au focal choisi');
})();
assert(FocalCropperMath.focalTopPercent(0, 630) === 0, 'focalTopPercent(0, 630) = 0% (aucune marge, cadre plein)');
assert(FocalCropperMath.focalBottomPercent(0, 630) === 0, 'focalBottomPercent(0, 630) = 0% (aucune marge, cadre plein)');

console.log(`\n${pass} passed, ${fail} failed`);
process.exit(fail > 0 ? 1 : 0);
