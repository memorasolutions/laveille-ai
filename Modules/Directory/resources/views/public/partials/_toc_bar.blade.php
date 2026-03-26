{{-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca --}}
{{-- Table des matieres sticky scrollspy pour les fiches outils --}}

@if(!empty($toc) && count($toc) > 2)
<nav class="rt-toc-bar" x-data="{ active: '' }" x-init="
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(e => { if (e.isIntersecting) active = e.target.id; });
    }, { rootMargin: '0px 0px -75% 0px' });
    document.querySelectorAll('.rt-description h2[id]').forEach(h2 => observer.observe(h2));
">
    <div class="rt-toc-scroll">
        @foreach($toc as $item)
            <a href="#{{ $item['id'] }}" class="rt-toc-link"
               :class="{ 'active': active === '{{ $item['id'] }}' }"
               @click.prevent="active = '{{ $item['id'] }}'; document.getElementById('{{ $item['id'] }}').scrollIntoView({ behavior: 'smooth', block: 'start' })">
                {{ $item['title'] }}
            </a>
        @endforeach
    </div>
</nav>
@endif
