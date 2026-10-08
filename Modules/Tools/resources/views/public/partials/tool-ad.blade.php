{{-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca --}}
{{-- AdSense non intrusif d'une page d'outil : rendu UNE seule fois, APRÈS la sortie de l'outil
     (jamais dans les contrôles interactifs, jamais au-dessus). Membres = aucune pub (AdsRenderer).
     Page @section('no_ads') = rien. Module Ads absent ou emplacement « tool-page » inactif = rien,
     aucune casse.
     Depuis 2026-10-08 : bloc REPLIABLE + mémorisé en cookie 7 jours, OUVERT par défaut (proposition
     fondateur, à la manière du bloc « En bref »). Pilotable par config ads.tool_collapsible (défaut
     true, désactivable via ADS_TOOL_COLLAPSIBLE=false). Le repli est VISUEL et survient APRÈS l'init
     Alpine : JAMAIS de x-cloak sur le bloc pub, pour que le push AdSense s'exécute sur un bloc
     VISIBLE au premier paint - une unité masquée au chargement ne se remplirait pas. --}}
@if(! ($isPreview ?? false) && ! \Illuminate\Support\Facades\View::hasSection('no_ads') && class_exists(\Modules\Ads\Services\AdsRenderer::class))
    @php $lvToolAd = app(\Modules\Ads\Services\AdsRenderer::class)->render('tool-page'); @endphp
    @if(filled($lvToolAd))
        @if(config('ads.tool_collapsible', true))
            <div class="container" style="max-width:680px;margin:24px auto 32px;" x-data="lvToolAdCollapse()">
                <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;margin-bottom:6px;">
                    <span style="font-size:11px;text-transform:uppercase;letter-spacing:.5px;color:#52586a;font-weight:600;">{{ __('Publicité') }}</span>
                    <button type="button" @click="toggle()" :aria-expanded="open ? 'true' : 'false'" aria-controls="lv-tool-ad-body"
                            style="display:inline-flex;align-items:center;gap:5px;min-height:34px;padding:5px 11px;border:1px solid #cbd5e1;border-radius:6px;background:#fff;color:#064E5A;font-size:12px;font-weight:600;cursor:pointer;line-height:1;">
                        <span x-text="open ? '{{ __('Réduire') }}' : '{{ __('Afficher la publicité') }}'"></span>
                        <span aria-hidden="true" x-text="open ? '&#9662;' : '&#9656;'"></span>
                    </button>
                </div>
                <div id="lv-tool-ad-body" x-show="open">{!! $lvToolAd !!}</div>
            </div>
        @else
            <div class="container" style="max-width:680px;margin:24px auto 32px;">{!! $lvToolAd !!}</div>
        @endif
    @endif
@endif
@once
    @push('scripts')
    <script>
    /* Pub d'outil repliable : ouverte par défaut, état mémorisé 7 jours en cookie. MEMORA solutions. */
    function lvToolAdCollapse() {
        return {
            open: true,
            init() {
                try { this.open = document.cookie.indexOf('lv_tool_ad_collapsed=1') === -1; }
                catch (e) { this.open = true; }
            },
            toggle() {
                this.open = !this.open;
                try {
                    document.cookie = 'lv_tool_ad_collapsed=' + (this.open ? '0' : '1') + ';path=/;max-age=604800;SameSite=Lax';
                } catch (e) {}
            }
        };
    }
    </script>
    @endpush
@endonce
