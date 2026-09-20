/*
 * Auteur : MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * Projet : laveille.ai
 *
 * Pont vers la visionneuse d'image UNIQUE du site (l'écouteur « lightbox » du layout).
 * Le corps d'un article est du HTML stocké en base et rendu tel quel : les directives
 * interactives y sont retirées au rendu, donc une figure d'article ne peut pas déclencher
 * la visionneuse par elle-même. Ce fichier ne fait que lui adresser ces figures - il ne
 * construit PAS une seconde visionneuse, et le lien reste un lien ordinaire sans lui.
 */
(function () {
    'use strict';

    document.addEventListener('click', function (event) {
        var target = event.target;
        var link;
        var href;
        var img;

        if (target && target.nodeType !== 1) {
            target = target.parentElement;
        }

        link = target ? target.closest('[data-lv-zoom]') : null;
        if (!link) {
            return;
        }

        // L'utilisateur veut visiblement un nouvel onglet : on le laisse faire.
        if (event.button === 1 || event.ctrlKey || event.metaKey ||
                event.shiftKey || event.altKey) {
            return;
        }

        href = link.getAttribute('href');
        if (!href) {
            return;
        }

        event.preventDefault();

        try {
            img = link.querySelector('img');
            window.dispatchEvent(new CustomEvent('lightbox', {
                detail: {
                    src: href,
                    alt: img ? (img.getAttribute('alt') || '') : ''
                }
            }));
        } catch (error) {
            // Jamais de clic mort : on bascule sur la navigation normale.
            window.location.assign(href);
        }
    });
}());
