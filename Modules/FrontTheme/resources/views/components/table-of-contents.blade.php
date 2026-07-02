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
        <p class="toc-title">{{ $title }}</p>
        <ol class="toc-list"></ol>
    </nav>
</details>

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
            top: 140px;
            width: 260px;
            z-index: 1000;
        }
        .toc-summary {
            display: none;
        }
        .toc {
            position: fixed;
            right: 24px;
            top: 140px;
            width: 260px;
            max-height: calc(100vh - 160px);
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
            if (!h.id) {
                let baseId = slugify(h.textContent) || 'heading';
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
            link.textContent = h.textContent;
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

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initTOC);
    } else {
        initTOC();
    }
})();
</script>
