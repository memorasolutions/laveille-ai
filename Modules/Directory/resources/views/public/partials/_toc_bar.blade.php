{{-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca --}}
{{-- Table des matieres fixe scrollspy pour les fiches outils --}}

@if(!empty($toc) && count($toc) > 2)
<div class="rt-toc-placeholder" x-data="{
    active: '',
    fixed: false,
    init() {
        // Scrollspy via IntersectionObserver
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(e => { if (e.isIntersecting) this.active = e.target.id; });
        }, { rootMargin: '0px 0px -75% 0px' });
        document.querySelectorAll('.rt-description h2[id]').forEach(h2 => observer.observe(h2));

        // Fixed bar quand la TOC sort du viewport
        const sentinel = this.$refs.sentinel;
        const fixedObs = new IntersectionObserver(([entry]) => {
            this.fixed = !entry.isIntersecting;
        }, { threshold: 0 });
        fixedObs.observe(sentinel);
    },
    goTo(id) {
        this.active = id;
        const el = document.getElementById(id);
        if (el) {
            const y = el.getBoundingClientRect().top + window.scrollY - 110;
            window.scrollTo({ top: y, behavior: 'smooth' });
        }
    }
}">
    {{-- Sentinel : quand il sort du viewport, la barre fixe apparait --}}
    <div x-ref="sentinel" style="height: 1px;"></div>

    {{-- Barre inline (position normale) --}}
    <nav class="rt-toc-bar" x-show="!fixed">
        <div class="rt-toc-scroll">
            @foreach($toc as $item)
                <a href="#{{ $item['id'] }}" class="rt-toc-link"
                   :class="{ 'active': active === '{{ $item['id'] }}' }"
                   @click.prevent="goTo('{{ $item['id'] }}')">
                    {{ $item['title'] }}
                </a>
            @endforeach
        </div>
    </nav>

    {{-- Barre fixe (apparait quand on scrolle au-dela) --}}
    <nav class="rt-toc-bar rt-toc-fixed" x-show="fixed" x-cloak x-transition.opacity>
        <div class="rt-toc-scroll">
            @foreach($toc as $item)
                <a href="#{{ $item['id'] }}" class="rt-toc-link"
                   :class="{ 'active': active === '{{ $item['id'] }}' }"
                   @click.prevent="goTo('{{ $item['id'] }}')">
                    {{ $item['title'] }}
                </a>
            @endforeach
        </div>
    </nav>
</div>
@endif
