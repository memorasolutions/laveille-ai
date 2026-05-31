{{-- S134 SEO : maillage contextuel des articles transitoires (actualités/blog) vers les hubs evergreen
     (glossaire + annuaire toujours présents, l'evergreen orphelin que le diagnostic veut nourrir) + les
     1-2 pages piliers les plus pertinentes selon $haystack (titre+catégorie+tags minuscules). Anti-boilerplate. --}}
@php
    $haystack = $haystack ?? '';
    $heading = $heading ?? __('Pour aller plus loin');

    $pillars = [
        ['route' => 'pillar.ia-pme', 'label' => __('IA pour les PME'), 'kw' => ['pme','entreprise','affaires','business','productivité','marketing','vente','gestion','employé']],
        ['route' => 'pillar.ia-education', 'label' => __('IA en éducation'), 'kw' => ['éducation','étudiant','école','tdah','apprentissage','université','cégep','enseign','cours','notebooklm','devoir']],
        ['route' => 'pillar.ia-dev', 'label' => __('IA pour les développeurs'), 'kw' => ['développeur','code','claude','mcp','llm','rag','api','programmation','cursor','token','local','technique','modèle']],
        ['route' => 'pillar.veille-ia', 'label' => __('Faire sa veille IA'), 'kw' => ['veille','source','actualité','tendance','hebdo','newsletter','suivre']],
        ['route' => 'pillar.ia-generative', 'label' => __('IA générative'), 'kw' => ['génératif','générative','image','vidéo','midjourney','dall-e','prompt','audio','création','sora','runway','hallucination']],
    ];

    $scores = [];
    foreach ($pillars as $pillar) {
        $score = 0;
        foreach ($pillar['kw'] as $kw) {
            if (str_contains($haystack, $kw)) {
                $score++;
            }
        }
        if ($score > 0) {
            $scores[] = ['route' => $pillar['route'], 'label' => $pillar['label'], 'score' => $score];
        }
    }

    usort($scores, fn($a, $b) => $b['score'] <=> $a['score']);
    $topPillars = array_slice($scores, 0, 2);

    $links = [
        ['route' => 'dictionary.index', 'label' => __('Glossaire IA')],
        ['route' => 'directory.index', 'label' => __("Annuaire d'outils IA")],
    ];

    foreach ($topPillars as $pillar) {
        $links[] = ['route' => $pillar['route'], 'label' => $pillar['label']];
    }

    // Filtrer les routes existantes (portabilité) et dédupliquer par route.
    $seen = [];
    $finalLinks = [];
    foreach ($links as $link) {
        if (! \Illuminate\Support\Facades\Route::has($link['route'])) {
            continue;
        }
        if (in_array($link['route'], $seen, true)) {
            continue;
        }
        $seen[] = $link['route'];
        $finalLinks[] = $link;
    }
@endphp

@if(! empty($finalLinks))
<nav aria-label="{{ $heading }}" style="margin-top:44px;padding-top:24px;border-top:1px solid var(--sys-border-subtle, #E5E7EB);">
    <h2 style="font-family: var(--f-heading); color: var(--sys-text-default, #1A1D23); font-size:1.15rem; margin-bottom:14px;">{{ $heading }}</h2>
    <ul style="list-style:none;padding:0;margin:0;display:flex;flex-wrap:wrap;gap:10px;">
        @foreach ($finalLinks as $link)
            <li>
                <a href="{{ route($link['route']) }}"
                   style="display:inline-block;padding:8px 14px;border:1px solid var(--sys-border-default, #D1D5DB);border-radius:var(--sys-radius-pill, 999px);color:var(--sys-text-link, #064E5A);text-decoration:none;font-size:0.9rem;font-weight:600;background:var(--sys-surface-raised, #F8FAFB);">
                    {{ $link['label'] }}
                </a>
            </li>
        @endforeach
    </ul>
</nav>
@endif
