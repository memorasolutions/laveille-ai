{{-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca --}}
{{-- AdSense non intrusif d'une page d'outil : rendu UNE seule fois, APRÈS la sortie de l'outil
     (jamais dans les contrôles interactifs, jamais au-dessus). Les outils sont l'aimant de
     rétention nº1 : la pub ne doit pas gêner l'usage. Membres = aucune pub (géré par AdsRenderer).
     Module Ads absent ou emplacement « tool-page » inactif = rien n'est rendu, aucune casse. --}}
@if(! ($isPreview ?? false) && class_exists(\Modules\Ads\Services\AdsRenderer::class))
    @php $lvToolAd = app(\Modules\Ads\Services\AdsRenderer::class)->render('tool-page'); @endphp
    @if(filled($lvToolAd))
        <div class="container" style="max-width:680px;margin:24px auto 32px;">{!! $lvToolAd !!}</div>
    @endif
@endif
