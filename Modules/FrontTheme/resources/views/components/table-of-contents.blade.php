<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@props([
    'contentSelector' => '.entry-details',
    'title' => __('Sommaire'),
])

<details class="toc-details">
    <summary class="toc-summary">{{ $title }}</summary>
    <nav
        id="toc-nav"
        class="toc"
        role="navigation"
        aria-label="{{ __('Sommaire de la page') }}"
        style="display: none;"
    >
        <a
            href="#main-content"
            class="toc-skip"
        >
            {{ __('Aller au contenu') }}
        </a>
        <button
            type="button"
            class="toc-close"
            aria-label="{{ __('Masquer le sommaire') }}"
        >&times;</button>
        <p class="toc-title">{{ $title }}</p>
        <ol class="toc-list"></ol>
    </nav>
</details>
<button
    type="button"
    id="toc-reopen"
    class="toc-reopen"
    aria-label="{{ __('Afficher le sommaire') }}"
>{{ __('☰ Sommaire') }}</button>

<style>
    .toc-details {
        display: block;
        margin: 0 0 1.5rem 0;
    }
    .toc-summary {
        display: none;
    }
    .toc {
        font-family: var(--f-heading), system-ui, sans-serif;
        font-size: 0.875rem;
        padding: 1rem;
        border-left: 1px solid rgba(6, 78, 90, 0.12);
        list-style: none;
        margin: 0;
    }
    /* #735 : masquer/rappeler n'a de sens que sur le panneau sticky desktop - mobile a déjà
       son propre mécanisme d'ouverture/fermeture natif (<details>/<summary>), inutile d'y
       dupliquer un second contrôle. Affichés uniquement dans le media query >=1400px. */
    .toc-close,
    .toc-reopen {
        display: none;
    }
    @media (max-width: 1399px) {
        .toc-details {
            position: relative;
        }
        .toc-summary {
            display: block;
            font-family: var(--f-heading), system-ui, sans-serif;
            font-weight: 600;
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: var(--c-primary);
            padding: 0.75rem 1rem;
            background: var(--c-primary-light);
            cursor: pointer;
            outline: none;
            border: 1px solid rgba(6, 78, 90, 0.12);
            border-radius: var(--r-base);
        }
        .toc-summary:focus-visible {
            outline: 2px solid var(--c-accent);
            outline-offset: 2px;
        }
        .toc-summary::-webkit-details-marker,
        .toc-summary::marker {
            display: none;
        }
        .toc {
            position: static !important;
            max-height: none !important;
            overflow-y: visible !important;
            display: block !important;
            margin-top: 0.5rem;
            border: 1px solid rgba(6, 78, 90, 0.12);
            border-top: none;
            border-top-left-radius: 0;
            border-top-right-radius: 0;
            background: #fff;
        }
        .toc-title {
            display: none;
        }
    }
    @media (min-width: 1400px) {
        .toc-details {
            position: fixed;
            right: 24px;
            top: 210px;
            width: 260px;
            z-index: 1000;
        }
        /* #739 : décalé de 140px à 210px - chevauchait la barre admin flottante
           (Modules/Core/components/admin-bar.blade.php, top:80px + son menu déroulant qui
           s'étend sous elle) signalée illisible/encombrée par l'utilisateur en tant
           qu'admin. Décalage universel (pas conditionné à l'admin) : reste une position
           tout à fait normale pour un visiteur non-admin, évite un couplage fragile entre
           composants indépendants. */
        /* #735 : sommaire sticky masquable - le garder toujours visible sans échappatoire
           peut devenir intrusif sur un long article (best practice 2026, pp_search
           2026-07-05 : bouton fermer réversible + mini-bascule persistante de rappel,
           qui ne recouvre pas le contenu). Masqué = ni le panneau ni son résumé mobile. */
        .toc-details.toc-dismissed {
            display: none;
        }
        .toc-summary {
            display: none;
        }
        .toc {
            position: fixed;
            right: 24px;
            top: 210px;
            width: 260px;
            max-height: calc(100vh - 230px);
            overflow-y: auto;
            display: block !important;
            border: 1px solid rgba(6, 78, 90, 0.12);
            border-radius: var(--r-base);
            background: #fff;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }
        .toc-title {
            font-family: var(--f-heading), system-ui, sans-serif;
            font-weight: 600;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: var(--c-text-muted);
            margin: 0 0 0.75rem 0;
            display: block;
        }
        .toc-close {
            display: flex;
            align-items: center;
            justify-content: center;
            position: absolute;
            top: 0;
            right: 0;
            width: 44px;
            height: 44px;
            padding: 0;
            border: none;
            border-radius: 999px;
            background: transparent;
            color: var(--c-text-muted);
            font-size: 1.25rem;
            line-height: 1;
            cursor: pointer;
        }
        .toc-close:hover {
            background: var(--c-primary-light, #F0FAFB);
            color: var(--c-primary);
        }
        .toc-close:focus-visible {
            outline: 2px solid var(--c-accent);
            outline-offset: 2px;
        }
        .toc-reopen {
            display: none;
            position: fixed;
            right: 24px;
            top: 210px;
            z-index: 1000;
            padding: 0.5rem 0.9rem;
            border: 1px solid rgba(6, 78, 90, 0.12);
            border-radius: 999px;
            background: #fff;
            color: var(--c-primary, #064E5A);
            font-family: var(--f-heading), system-ui, sans-serif;
            font-size: 0.8rem;
            font-weight: 600;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            cursor: pointer;
        }
        .toc-reopen:hover {
            background: var(--c-primary-light, #F0FAFB);
        }
        .toc-reopen:focus-visible {
            outline: 2px solid var(--c-accent);
            outline-offset: 2px;
        }
        .toc-reopen.is-visible {
            display: block;
        }
    }
    .toc-list {
        list-style: none;
        padding: 0;
        margin: 0;
    }
    .toc-list ol {
        list-style: none;
        padding-left: 0.75rem;
        margin: 0.25rem 0 0 0;
        border-left: 1px solid rgba(6, 78, 90, 0.1);
    }
    .toc-list li {
        margin: 0.125rem 0;
    }
    .toc-link {
        display: block;
        padding: 0.25rem 0.5rem;
        color: var(--c-text-secondary);
        text-decoration: none;
        border-left: 3px solid transparent;
        transition: color 0.2s, border-color 0.2s;
        line-height: 1.4;
    }
    @media (max-width: 1399px) {
        .toc-link {
            padding: 0.75rem 1rem;
            min-height: 44px;
            display: flex;
            align-items: center;
        }
    }
    .toc-link:hover {
        color: var(--c-primary);
    }
    /* Badge de numéro d'ordre (ex. sections "Concentré IA" hebdo) - séparé du titre plutôt
       que baké dans le texte : corrélation avec le corps de l'article conservée, sans
       recréer un simple "14. Titre" en texte brut. .toc-list ayant list-style:none, c'est
       la SEULE numérotation visible du sommaire pour ces titres (#723, 2026-07-05). */
    .toc-num {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 1.25rem;
        height: 1.25rem;
        padding: 0 0.3rem;
        margin-right: 0.4rem;
        border-radius: 999px;
        background: var(--c-primary-light, #F0FAFB);
        color: var(--c-primary, #064E5A);
        font-size: 0.7rem;
        font-weight: 700;
        font-variant-numeric: tabular-nums;
        vertical-align: middle;
    }
    .toc-link.toc-active .toc-num {
        background: var(--c-primary, #064E5A);
        color: #fff;
    }
    .toc-link.toc-active {
        font-weight: 600;
        color: var(--c-primary);
        border-left-color: var(--c-primary);
    }
    .toc-link:focus-visible {
        outline: 2px solid var(--c-accent);
        outline-offset: 2px;
    }
    .toc-skip {
        position: absolute;
        width: 1px;
        height: 1px;
        padding: 0;
        margin: -1px;
        overflow: hidden;
        clip: rect(0,0,0,0);
        white-space: nowrap;
        border-width: 0;
    }
    /* Sélecteur renforcé (.toc-details .toc-skip:focus) : une règle thème/Bootstrap
       a:focus{color:var(--bs-navbar-active-color)} de même spécificité, chargée après ce
       composant, écrasait color:#fff (2.08:1, fail même AA) — audit WCAG AAA 2026-07-02.
       #fff sur --c-primary (#064E5A) = 9.35:1 AAA. */
    .toc-details .toc-skip:focus {
        position: static;
        width: auto;
        height: auto;
        clip: auto;
        padding: 0.5rem 0.75rem;
        background: var(--c-primary);
        color: #fff;
        outline: 3px solid var(--c-accent);
        z-index: 1001;
    }
</style>

<script>
(function () {
    'use strict';
    const TOC_ID = 'toc-nav';
    const CONTENT_SELECTOR = '{{ $contentSelector }}';
    const MIN_HEADINGS = 2;

    function slugify(text) {
        return text.toLowerCase()
            .normalize('NFD').replace(/[̀-ͯ]/g, '')
            .replace(/[^\w\s-]/g, '')
            .replace(/[\s_-]+/g, '-')
            .replace(/^-+|-+$/g, '');
    }

    // Certains titres H2 (ex. "Concentré IA" hebdo) portent un numéro d'ordre en dur
    // ("14. Titre"). Correctif #723 (2026-07-05) : .toc-list a `list-style: none` (pas de
    // numérotation native du <ol>) - un premier correctif retirait le numéro du texte du
    // lien en pensant qu'il ferait doublon avec une numérotation native inexistante,
    // ce qui cassait en réalité la corrélation visuelle avec le "14." resté dans le corps
    // de l'article (capture utilisateur 2026-07-05_14-10-23.jpg). Fix corrigé : le numéro
    // est conservé mais extrait dans un badge distinct (`.toc-num`), séparé du titre -
    // corrélation intacte avec le corps, ancre TOUJOURS sans le chiffre en préfixe (ex.
    // #titre au lieu de #14-titre, qui restait le vrai défaut signalé au départ).
    function extractListNumber(text) {
        const m = text.match(/^\s*(\d+)[.)]\s*(.*)$/s);
        return m ? { num: m[1], rest: m[2] } : { num: null, rest: text };
    }

    function initTOC() {
        const tocNav = document.getElementById(TOC_ID);
        if (!tocNav) return;
        const container = document.querySelector(CONTENT_SELECTOR);
        if (!container) return;
        // Exclut les titres internes à des widgets encartés (ex. x-fronttheme::book-promo) :
        // pollution éditoriale hors-sujet dans le sommaire, pas le contenu de l'article - audit WCAG 2026-07-02.
        const headings = Array.from(container.querySelectorAll('h2, h3'))
            .filter(h => (h.id || h.textContent.trim()))
            .filter(h => !h.closest('.lv-book-promo'));
        if (headings.length < MIN_HEADINGS) return;

        const usedIds = new Set();
        headings.forEach(h => {
            const parsedForId = extractListNumber(h.textContent);
            // Certains articles (ex. "Concentré IA" déjà publiés) ont un id BAKÉ EN DUR dans
            // le HTML stocké (ex. id="14-titre", écrit à la création du contenu, PAS généré
            // par ce script) - un simple `if (!h.id)` le préservait tel quel, donc l'ancre
            // moche restait active même après ce correctif (signalé par l'utilisateur en
            // revisitant l'URL exacte de son premier signalement). Détecté via le même
            // numéro que celui trouvé dans le texte du titre -> régénéré proprement ; tout
            // id NE correspondant PAS à ce motif (ex. ids personnalisés Académie) reste
            // intact, comme avant.
            const isLegacyNumberedId = h.id && parsedForId.num && h.id.startsWith(parsedForId.num + '-');
            if (!h.id || isLegacyNumberedId) {
                let baseId = slugify(parsedForId.rest) || 'heading';
                let id = baseId, counter = 1;
                while (usedIds.has(id) || document.getElementById(id)) { id = `${baseId}-${counter++}`; }
                h.id = id;
            }
            usedIds.add(h.id);
            h.style.scrollMarginTop = '90px';
            // WCAG 2.4.3 : rend le titre ciblé programmatiquement focusable (sans l'ajouter
            // à l'ordre de tabulation naturel) pour que le focus clavier suive le scroll - audit 2026-07-02.
            if (!h.hasAttribute('tabindex')) h.setAttribute('tabindex', '-1');
        });

        const tocList = tocNav.querySelector('.toc-list');
        tocList.innerHTML = '';
        let currentH2Item = null;
        headings.forEach(h => {
            const link = document.createElement('a');
            link.href = `#${h.id}`;
            const parsed = extractListNumber(h.textContent);
            if (parsed.num) {
                const badge = document.createElement('span');
                badge.className = 'toc-num';
                badge.textContent = parsed.num;
                link.appendChild(badge);
                link.appendChild(document.createTextNode(parsed.rest));
            } else {
                link.textContent = h.textContent;
            }
            link.className = 'toc-link';
            link.dataset.target = h.id;
            const li = document.createElement('li');
            li.appendChild(link);
            if (h.tagName === 'H2') {
                tocList.appendChild(li);
                currentH2Item = li;
            } else if (currentH2Item) {
                let sub = currentH2Item.querySelector('ol');
                if (!sub) {
                    sub = document.createElement('ol');
                    currentH2Item.appendChild(sub);
                }
                sub.appendChild(li);
            } else {
                tocList.appendChild(li);
            }
        });

        const links = tocNav.querySelectorAll('.toc-link');
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const link = tocNav.querySelector(`.toc-link[data-target="${entry.target.id}"]`);
                    if (!link) return;
                    links.forEach(l => {
                        l.classList.remove('toc-active');
                        l.removeAttribute('aria-current');
                    });
                    link.classList.add('toc-active');
                    link.setAttribute('aria-current', 'location');
                }
            });
        }, { rootMargin: '-80px 0px -75% 0px', threshold: 0 });
        headings.forEach(h => observer.observe(h));

        links.forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const target = document.getElementById(link.dataset.target);
                if (!target) return;
                const offset = 80;
                const y = target.getBoundingClientRect().top + window.scrollY - offset;
                window.scrollTo({ top: y, behavior: 'smooth' });
                history.pushState(null, '', `#${link.dataset.target}`);
                // WCAG 2.4.3 Ordre du focus : le focus clavier doit suivre la cible visuelle,
                // pas rester sur le lien du sommaire - audit AAA 2026-07-02.
                target.focus({ preventScroll: true });
            });
        });

        tocNav.style.display = 'block';

        // Le sommaire est deplie automatiquement sur grand ecran, replie sur mobile/tablette.
        const details = tocNav.closest('.toc-details');
        if (details) {
            const mediaQuery = window.matchMedia('(min-width: 1400px)');
            const toggleDetails = () => {
                details.open = mediaQuery.matches;
            };
            toggleDetails();
            mediaQuery.addEventListener('change', toggleDetails);
        }
    }

    // #735/#739 : masquer/rappeler le sommaire sticky desktop (préférence persistée
    // localStorage, comme le sélecteur clair/sombre déjà présent sur le site) - un
    // sommaire toujours visible sans échappatoire peut devenir intrusif sur un long
    // article. Portée volontairement limitée au panneau desktop (>=1400px) : mobile a
    // déjà son propre mécanisme d'ouverture/fermeture natif (<details>/<summary>).
    // #739 : FERMÉ par défaut (demande utilisateur) - seule une ouverture explicite via
    // le bouton de rappel persiste ; l'absence de préférence = fermé, pas ouvert.
    function initDismiss() {
        const detailsEl = document.querySelector('.toc-details');
        const reopenBtn = document.getElementById('toc-reopen');
        const closeBtn = document.querySelector('.toc-close');
        if (!detailsEl || !reopenBtn || !closeBtn) return;
        const STORAGE_KEY = 'toc_open';

        function applyState(dismissed) {
            detailsEl.classList.toggle('toc-dismissed', dismissed);
            reopenBtn.classList.toggle('is-visible', dismissed);
        }

        let open = false;
        try { open = localStorage.getItem(STORAGE_KEY) === '1'; } catch (e) {}
        applyState(!open);

        closeBtn.addEventListener('click', () => {
            applyState(true);
            try { localStorage.setItem(STORAGE_KEY, '0'); } catch (e) {}
        });
        reopenBtn.addEventListener('click', () => {
            applyState(false);
            try { localStorage.setItem(STORAGE_KEY, '1'); } catch (e) {}
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initTOC);
        document.addEventListener('DOMContentLoaded', initDismiss);
    } else {
        initTOC();
        initDismiss();
    }
})();
</script>
