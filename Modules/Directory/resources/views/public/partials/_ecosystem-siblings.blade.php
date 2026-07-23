{{-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca --}}
{{--
    S135 2026-07-23 : section "Autres outils de [Éditeur]" (pattern App Store), affichée sur la
    fiche outil publique quand $tool->ecosystem_tag est renseigné et qu'au moins un autre outil
    publié partage ce même tag. Requiert $tool (toujours disponible dans public.show).

    Note d'intégration : ce partial fait sa propre petite requête (limitée à 4 résultats, sur
    colonne indexée ecosystem_tag) directement en vue — le contrôleur PublicDirectoryController
    n'a pas encore été modifié pour préparer cette donnée côté backend (travail en parallèle,
    hors périmètre frontend de cette tâche). Aucun fichier PHP de Modules/Directory/app/ n'est
    touché ici.
--}}
@php
    $ecoTag = $tool->ecosystem_tag ?? null;
    $ecoSiblings = collect();
    $ecoLabel = null;
    if ($ecoTag) {
        $ecoLabel = config('ecosystems.labels')[$ecoTag] ?? ucfirst($ecoTag);
        $ecoSiblings = \Modules\Directory\Models\Tool::published()
            ->where('ecosystem_tag', $ecoTag)
            ->where('id', '!=', $tool->id)
            ->orderByDesc('clicks_count')
            ->limit(4)
            ->get();
    }
@endphp
@if($ecoTag && $ecoSiblings->isNotEmpty())
<section class="rt-ecosystem-siblings" aria-labelledby="rt-eco-siblings-title" style="padding:24px 0;border-top:1px solid #eee;margin-top:12px;">
    <div class="container">
        <h3 id="rt-eco-siblings-title" style="font-family:var(--f-heading);font-size:16px;font-weight:700;color:var(--c-dark);margin:0 0 4px;">
            {{ __('Autres outils de :editor', ['editor' => $ecoLabel]) }}
        </h3>
        <p style="font-size:13px;color:#6B7280;margin:0 0 14px;">
            {{ __('Découvrez le reste de la gamme :editor.', ['editor' => $ecoLabel]) }}
        </p>
        <div class="row">
            @foreach($ecoSiblings as $sib)
                @php $sibHost = $sib->url ? parse_url($sib->url, PHP_URL_HOST) : ''; @endphp
                <div class="col-md-3 col-sm-6 col-xs-12" style="margin-bottom:12px;">
                    <a href="{{ $sib->getPublicUrl() }}" style="display:block;text-align:center;border:1px solid #E5E7EB;border-radius:var(--r-base);padding:16px;text-decoration:none;color:inherit;transition:all .2s;height:100%;">
                        @if($sibHost)<x-core::smart-favicon :domain="$sibHost" :size="32" />@endif
                        <div style="font-family:var(--f-heading);font-weight:700;font-size:.95rem;margin-top:6px;">{{ $sib->name }}</div>
                        <span class="rt-badge badge-{{ $sib->pricing }}" style="font-size:.6rem;margin-top:6px;">{{ \Modules\Directory\Support\PricingCategories::labels()[$sib->pricing] ?? ucfirst($sib->pricing) }}</span>
                    </a>
                </div>
            @endforeach
        </div>
        <div style="margin-top:6px;">
            <a href="{{ route('directory.index') }}?ecosystem={{ $ecoTag }}" style="font-size:13px;color:var(--c-primary,#064E5A);text-decoration:none;font-weight:600;">
                {{ __('Voir tous les outils :editor', ['editor' => $ecoLabel]) }} &rarr;
            </a>
        </div>
    </div>
</section>
@endif
