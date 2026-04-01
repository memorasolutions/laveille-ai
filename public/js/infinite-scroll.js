/**
 * Infinite Scroll — composant réutilisable laveille.ai
 * Se greffe automatiquement sur toute pagination Laravel (.pagination).
 * Aucune modification de vue nécessaire.
 *
 * Fonctionnement :
 * - Détecte le lien "page suivante" dans la pagination
 * - IntersectionObserver déclenche le chargement quand on approche du bas
 * - Append les nouveaux éléments dans le container parent
 * - Remplace la pagination par un spinner de chargement
 * - Fallback : bouton "Charger plus" si JS erreur
 */
(function() {
    'use strict';

    // Chercher la pagination sur la page
    var pagination = document.querySelector('.pagination');
    if (!pagination) return;

    // Trouver le lien "page suivante"
    function getNextUrl() {
        var next = pagination.parentElement.querySelector('a[rel="next"]')
            || pagination.querySelector('.page-item:not(.disabled):last-child a')
            || pagination.querySelector('a[rel="next"]');
        return next ? next.href : null;
    }

    var nextUrl = getNextUrl();
    if (!nextUrl) return; // Pas de page suivante = rien à faire

    // Trouver le container de contenu (parent de la pagination, remonter jusqu'au container de liste)
    var paginationParent = pagination.closest('.card-footer') || pagination.parentElement;
    var contentContainer = null;

    // Chercher le container le plus probable (table tbody, .row, liste)
    var candidates = ['.news-grid', 'tbody', '.row', '[class*="list"]', '.d-flex.flex-column'];
    var searchRoot = paginationParent.closest('.container') || paginationParent.closest('section') || document;
    for (var i = 0; i < candidates.length; i++) {
        var found = searchRoot.querySelector(candidates[i]);
        if (found && found.children.length > 1) {
            contentContainer = found;
            break;
        }
    }

    if (!contentContainer) {
        // Fallback : le sibling précédent de la pagination
        contentContainer = paginationParent.previousElementSibling;
    }

    if (!contentContainer) return;

    // Remplacer la pagination par un loader
    var loading = false;
    var loader = document.createElement('div');
    loader.style.cssText = 'text-align:center;padding:20px;display:none;';
    loader.innerHTML = '<div style="display:inline-block;width:30px;height:30px;border:3px solid #e5e7eb;border-top-color:var(--c-primary, #0B7285);border-radius:50%;animation:spin 0.8s linear infinite;"></div>';

    // Ajouter le style d'animation si pas déjà présent
    if (!document.querySelector('#infinite-scroll-style')) {
        var style = document.createElement('style');
        style.id = 'infinite-scroll-style';
        style.textContent = '@keyframes spin { to { transform: rotate(360deg); } }';
        document.head.appendChild(style);
    }

    // Cacher la pagination originale et ajouter le loader
    pagination.parentElement.style.display = 'none';
    pagination.parentElement.parentElement.appendChild(loader);

    // Sentinel element (trigger point)
    var sentinel = document.createElement('div');
    sentinel.style.height = '1px';
    loader.parentElement.insertBefore(sentinel, loader);

    function loadMore() {
        if (loading || !nextUrl) return;
        loading = true;
        loader.style.display = 'block';

        fetch(nextUrl, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function(r) { return r.text(); })
            .then(function(html) {
                var parser = new DOMParser();
                var doc = parser.parseFromString(html, 'text/html');

                // Trouver le même container dans la nouvelle page
                var newContent = null;
                for (var i = 0; i < candidates.length; i++) {
                    newContent = doc.querySelector(candidates[i]);
                    if (newContent && newContent.children.length > 0) break;
                    newContent = null;
                }

                if (newContent) {
                    // Append les enfants
                    var children = Array.from(newContent.children);
                    children.forEach(function(child) {
                        contentContainer.appendChild(child.cloneNode(true));
                    });
                }

                // Trouver la prochaine URL
                var newPagination = doc.querySelector('.pagination');
                if (newPagination) {
                    var newNext = newPagination.parentElement.querySelector('a[rel="next"]')
                        || newPagination.querySelector('a[rel="next"]');
                    nextUrl = newNext ? newNext.href : null;
                } else {
                    nextUrl = null;
                }

                loading = false;
                loader.style.display = 'none';

                if (!nextUrl) {
                    sentinel.remove();
                    loader.remove();
                }
            })
            .catch(function() {
                loading = false;
                loader.style.display = 'none';
            });
    }

    // IntersectionObserver pour auto-load
    if ('IntersectionObserver' in window) {
        var observer = new IntersectionObserver(function(entries) {
            if (entries[0].isIntersecting) {
                loadMore();
            }
        }, { rootMargin: '300px' });
        observer.observe(sentinel);
    } else {
        // Fallback pour vieux navigateurs : bouton "Charger plus"
        var btn = document.createElement('button');
        btn.textContent = 'Charger plus';
        btn.className = 'btn btn-primary';
        btn.style.cssText = 'display:block;margin:20px auto;';
        btn.onclick = loadMore;
        loader.parentElement.insertBefore(btn, loader);
    }
})();
