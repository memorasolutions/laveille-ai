@props([
    'contentSelector' => '.author-article-body',
])

<nav
    id="toc-nav"
    class="toc"
    role="navigation"
    aria-label="Table des matières"
    style="display: none;"
>
    <a
        href="#main-content"
        class="toc-skip"
    >
        Aller au contenu
    </a>
    <p class="toc-title">Sommaire</p>
    <ol class="toc-list"></ol>
</nav>

<style>
    .toc {
        position: sticky;
        top: 100px;
        max-height: calc(100vh - 120px);
        overflow-y: auto;
        font-family: 'DM Sans', ui-sans-serif, system-ui, sans-serif;
        font-size: 0.875rem;
        padding: 1rem;
        border-left: 1px solid rgba(11, 114, 133, 0.12);
    }
    @media (max-width: 1023px) {
        .toc { display: none !important; }
    }
    .toc-title {
        font-family: 'Plus Jakarta Sans', sans-serif;
        font-weight: 600;
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        color: var(--c-text-muted);
        margin: 0 0 0.75rem 0;
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
        border-left: 1px solid rgba(11, 114, 133, 0.1);
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
    .toc-link:hover {
        color: var(--c-primary);
    }
    .toc-link.toc-active {
        font-weight: 600;
        color: var(--c-primary);
        border-left-color: var(--c-primary);
    }
    .toc-skip {
        position: absolute;
        width: 1px; height: 1px;
        padding: 0; margin: -1px;
        overflow: hidden;
        clip: rect(0,0,0,0);
        white-space: nowrap;
        border-width: 0;
    }
    .toc-skip:focus {
        position: static;
        width: auto; height: auto;
        clip: auto;
        padding: 0.5rem 0.75rem;
        background: var(--c-primary);
        color: #fff;
        outline: 3px solid var(--c-accent);
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
        const headings = Array.from(container.querySelectorAll('h2, h3'))
            .filter(h => (h.id || h.textContent.trim()));
        if (headings.length < MIN_HEADINGS) return;

        const usedIds = new Set();
        headings.forEach(h => {
            if (!h.id) {
                let baseId = slugify(h.textContent) || 'heading';
                let id = baseId, counter = 1;
                while (usedIds.has(id) || document.getElementById(id)) {
                    id = `${baseId}-${counter++}`;
                }
                h.id = id;
            }
            usedIds.add(h.id);
            h.style.scrollMarginTop = '90px';
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
                if (!sub) { sub = document.createElement('ol'); currentH2Item.appendChild(sub); }
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
                    links.forEach(l => { l.classList.remove('toc-active'); l.removeAttribute('aria-current'); });
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
            });
        });

        tocNav.style.display = '';
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initTOC);
    } else {
        initTOC();
    }
})();
</script>
