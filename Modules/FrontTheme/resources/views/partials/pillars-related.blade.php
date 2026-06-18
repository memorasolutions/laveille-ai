{{-- S134 SEO : cross-linking des pages piliers (cluster thématique). $current = nom de route du pilier courant à exclure. --}}
@php
    $lvPillars = [
        ['route' => 'pillar.ia-pme', 'label' => __('IA pour les PME québécoises')],
        ['route' => 'pillar.ia-education', 'label' => __('IA en éducation (TDAH, étudiants)')],
        ['route' => 'pillar.ia-dev', 'label' => __('IA pour les développeurs (Claude, MCP)')],
        ['route' => 'pillar.veille-ia', 'label' => __('Faire sa veille IA au Québec')],
        ['route' => 'pillar.ia-generative', 'label' => __('IA générative (texte, image, vidéo)')],
        ['route' => 'pillar.ia-secteur-public', 'label' => __('IA dans le secteur public québécois')],
    ];
    $lvCurrent = $current ?? null;
    $lvList = array_values(array_filter($lvPillars, fn ($p) => $p['route'] !== $lvCurrent && \Illuminate\Support\Facades\Route::has($p['route'])));
@endphp
@if(count($lvList))
<nav aria-label="{{ __('Dossiers thématiques connexes') }}" style="margin-top:44px;padding-top:24px;border-top:1px solid var(--sys-border-subtle, #E5E7EB);">
    <h2 style="font-family: var(--f-heading); color: var(--sys-text-default, #1A1D23); font-size:1.15rem; margin-bottom:14px;">{{ __('Dossiers thématiques connexes') }}</h2>
    <ul style="list-style:none;padding:0;margin:0;display:flex;flex-wrap:wrap;gap:10px;">
        @foreach($lvList as $p)
            <li>
                <a href="{{ route($p['route']) }}"
                   style="display:inline-block;padding:8px 14px;border:1px solid var(--sys-border-default, #D1D5DB);border-radius:var(--sys-radius-pill, 999px);color:var(--sys-text-link, #064E5A);text-decoration:none;font-size:0.9rem;font-weight:600;background:var(--sys-surface-raised, #F8FAFB);">{{ $p['label'] }}</a>
            </li>
        @endforeach
    </ul>
</nav>
@endif
