// public/assets/directory/focal-cropper-math.js
// Design doc 2026-08-10 (recadrage frontend) - moteur de calcul PUR (zero DOM) du composant
// x-core::focal-cropper. Charge par le composant via <script src> ET testable directement par
// Node (tests/js/focal-cropper-math.test.cjs) - meme pattern d'export CJS que
// public/assets/tools/anonymiseur/anonymizer-core.js:761.
//
// @author MEMORA solutions <info@memora.ca>
(function (root) {
    'use strict';

    var THUMB_W = 1200;
    var THUMB_H = 630;

    function clamp(value, min, max) {
        return Math.min(max, Math.max(min, value));
    }

    // Hauteur normalisee du master : jamais < 630 (sinon aucune marge de recadrage), jamais > 1400
    // (regle serveur - DirectoryAdminController::deriveMasterFromUpload(), jamais un 770 en dur ici).
    function normalizedMasterHeight(rawHeight) {
        var h = Math.round(Number(rawHeight) || THUMB_H);

        return clamp(h, THUMB_H, 1400);
    }

    // Amplitude verticale disponible pour le point focal, derivee de la hauteur REELLE du master
    // (jamais une constante partagee entre tous les outils).
    function maxFocal(masterHeight) {
        return Math.max(0, normalizedMasterHeight(masterHeight) - THUMB_H);
    }

    function clampFocal(focalY, masterHeight) {
        return clamp(Math.round(Number(focalY) || 0), 0, maxFocal(masterHeight));
    }

    // Convertit un deplacement pointeur en pixels ECRAN (dans le cadre affiche a l'echelle
    // displayScale = pixels ecran par pixel master) en deplacement du point focal en pixels master.
    // Glisser l'image vers le HAUT (deltaScreenPx negatif) doit faire DESCENDRE le point focal
    // (on revele une portion plus basse du master) - d'ou le signe negatif.
    function pointerDeltaToFocalDelta(deltaScreenPx, displayScale) {
        if (!displayScale) {
            return 0;
        }

        return -(Number(deltaScreenPx) || 0) / displayScale;
    }

    // Position du haut du cadre net, en % de la hauteur du master (pour le clip-path / l'indicateur).
    function focalTopPercent(focalY, masterHeight) {
        var h = normalizedMasterHeight(masterHeight);

        return h > 0 ? (clampFocal(focalY, masterHeight) / h) * 100 : 0;
    }

    // Position du bas exclu, en % de la hauteur du master (pour clip-path: inset(top 0 bottom 0)).
    function focalBottomPercent(focalY, masterHeight) {
        var h = normalizedMasterHeight(masterHeight);
        var focal = clampFocal(focalY, masterHeight);

        return h > 0 ? Math.max(0, (h - focal - THUMB_H) / h) * 100 : 0;
    }

    // Fraction de la HAUTEUR de la vignette qui survit reellement a l'affichage sur une page
    // publique. Une page affiche la vignette en `width: 100%` avec une hauteur PLAFONNEE et
    // `object-fit: cover` : des que la hauteur naturelle (largeur / ratio 1200x630) depasse ce
    // plafond, le navigateur rogne la difference, moitie en haut, moitie en bas.
    //
    // Mesure du 2026-08-25 : la fiche d'un outil (plafond 400 px, boite 1146 px) n'en montre que
    // 66,5 %, tandis qu'une fiche d'actualite (plafond 420 px, boite 740 px) les montre EN ENTIER.
    // C'est pourquoi cette fraction se calcule sur les dimensions reelles de la page visee et ne
    // peut jamais etre une constante ecrite dans le composant : elle differe selon la page ET
    // selon la largeur de l'ecran.
    //
    // Retourne null quand la question n'a pas de reponse fiable (dimensions absentes ou nulles),
    // ce que l'appelant doit traiter comme « je ne sais pas », donc n'affiche AUCUN repere -
    // jamais un repere faux.
    function visibleHeightFraction(displayedWidth, maxDisplayedHeight) {
        var w = Number(displayedWidth) || 0;
        var maxH = Number(maxDisplayedHeight) || 0;

        if (w <= 0 || maxH <= 0) {
            return null;
        }

        var naturalHeight = (w * THUMB_H) / THUMB_W;

        if (naturalHeight <= 0) {
            return null;
        }

        return clamp(maxH / naturalHeight, 0, 1);
    }

    // Part rognee de CHAQUE cote (haut et bas), en % de la hauteur de la vignette. `object-fit:
    // cover` centre l'image, donc la perte est symetrique. 0 quand rien n'est rogne.
    function croppedSidePercent(fraction) {
        var f = Number(fraction);

        if (!isFinite(f) || f <= 0 || f >= 1) {
            return 0;
        }

        return ((1 - f) / 2) * 100;
    }

    var api = {
        THUMB_W: THUMB_W,
        THUMB_H: THUMB_H,
        clamp: clamp,
        normalizedMasterHeight: normalizedMasterHeight,
        maxFocal: maxFocal,
        clampFocal: clampFocal,
        pointerDeltaToFocalDelta: pointerDeltaToFocalDelta,
        focalTopPercent: focalTopPercent,
        focalBottomPercent: focalBottomPercent,
        visibleHeightFraction: visibleHeightFraction,
        croppedSidePercent: croppedSidePercent,
    };

    if (typeof module !== 'undefined' && module.exports) {
        module.exports = api;
    } else {
        root.FocalCropperMath = api;
    }
})(typeof window !== 'undefined' ? window : globalThis);
