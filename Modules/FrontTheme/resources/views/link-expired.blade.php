@extends(fronttheme_layout())

@php $lvReason = $reason; @endphp

@section('title', ($lvReason === 'expired' ? __('QR code expiré') : __('Lien introuvable')) . ' - ' . config('app.name'))

@section('breadcrumb')
    @include('fronttheme::partials.breadcrumb', ['breadcrumbTitle' => $lvReason === 'expired' ? __('Lien expiré') : __('Lien introuvable')])
@endsection

@section('content')
<section class="wpo-blog-single-section section-padding">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12" style="max-width:720px;">

                {{-- Hero : emoji + titre + accroche --}}
                <div class="text-center" style="padding: 40px 20px 32px;">
                    <div style="font-size: 4rem; margin-bottom: 16px; line-height: 1;">
                        {{ $lvReason === 'expired' ? '⏰' : '🔗' }}
                    </div>
                    <h1 style="font-family: var(--f-heading); font-weight: 800; color: var(--c-dark); font-size: clamp(1.4rem, 4vw, 2rem); margin-bottom: 16px;">
                        @if($lvReason === 'expired')
                            {{ __("Ce QR code n'est plus actif… mais t'es au bon endroit !") }}
                        @else
                            {{ __('Ce lien est introuvable') }}
                        @endif
                    </h1>
                    <p style="color: #374151; font-size: 1.05rem; line-height: 1.7; margin-bottom: 8px; max-width: 560px; margin-left: auto; margin-right: auto;">
                        @if($lvReason === 'expired')
                            {{ __("Le QR ou le lien que tu as scanné a expiré ou a été désactivé. Pas de panique — tu peux explorer nos ressources IA gratuites ci-dessous.") }}
                        @else
                            {{ __("Ce lien n'existe pas ou a été supprimé. Vérifie l'URL et réessaie.") }}
                        @endif
                    </p>
                </div>

                {{-- Ressources IA (reason=expired uniquement) --}}
                @if($lvReason === 'expired')
                <div style="margin-bottom: 40px;">
                    <h2 style="font-family: var(--f-heading); font-weight: 700; font-size: 1.15rem; color: var(--c-dark); margin-bottom: 18px; text-align: center;">
                        {{ __('Explore nos ressources IA gratuites') }}
                    </h2>
                    <div class="row g-3">
                        @if(Route::has('dictionary.index'))
                        <div class="col-12 col-sm-6">
                            <a href="{{ route('dictionary.index') }}" style="text-decoration:none;" class="d-block h-100">
                                <div style="background:#fff;border:1px solid #E5E7EB;border-radius:12px;padding:18px 20px;height:100%;transition:border-color .15s;" onmouseover="this.style.borderColor='var(--c-primary)'" onmouseout="this.style.borderColor='#E5E7EB'">
                                    <div style="font-weight:700;font-family:var(--f-heading);color:var(--c-dark);margin-bottom:5px;">{{ __('Glossaire Techno') }}</div>
                                    <div style="font-size:0.85rem;color:#6B7280;">{{ __('400+ termes expliqués en français') }}</div>
                                </div>
                            </a>
                        </div>
                        @endif
                        @if(Route::has('directory.index'))
                        <div class="col-12 col-sm-6">
                            <a href="{{ route('directory.index') }}" style="text-decoration:none;" class="d-block h-100">
                                <div style="background:#fff;border:1px solid #E5E7EB;border-radius:12px;padding:18px 20px;height:100%;transition:border-color .15s;" onmouseover="this.style.borderColor='var(--c-primary)'" onmouseout="this.style.borderColor='#E5E7EB'">
                                    <div style="font-weight:700;font-family:var(--f-heading);color:var(--c-dark);margin-bottom:5px;">{{ __("Annuaire d'outils IA") }}</div>
                                    <div style="font-size:0.85rem;color:#6B7280;">{{ __('354 outils comparés') }}</div>
                                </div>
                            </a>
                        </div>
                        @endif
                        @if(Route::has('tools.index'))
                        <div class="col-12 col-sm-6">
                            <a href="{{ route('tools.index') }}" style="text-decoration:none;" class="d-block h-100">
                                <div style="background:#fff;border:1px solid #E5E7EB;border-radius:12px;padding:18px 20px;height:100%;transition:border-color .15s;" onmouseover="this.style.borderColor='var(--c-primary)'" onmouseout="this.style.borderColor='#E5E7EB'">
                                    <div style="font-weight:700;font-family:var(--f-heading);color:var(--c-dark);margin-bottom:5px;">{{ __('Outils interactifs') }}</div>
                                    <div style="font-size:0.85rem;color:#6B7280;">{{ __('QR, mots croisés, quiz, anonymiseur…') }}</div>
                                </div>
                            </a>
                        </div>
                        @endif
                        @if(Route::has('blog.index'))
                        <div class="col-12 col-sm-6">
                            <a href="{{ route('blog.index') }}" style="text-decoration:none;" class="d-block h-100">
                                <div style="background:#fff;border:1px solid #E5E7EB;border-radius:12px;padding:18px 20px;height:100%;transition:border-color .15s;" onmouseover="this.style.borderColor='var(--c-primary)'" onmouseout="this.style.borderColor='#E5E7EB'">
                                    <div style="font-weight:700;font-family:var(--f-heading);color:var(--c-dark);margin-bottom:5px;">{{ __('Concentré IA hebdo') }}</div>
                                    <div style="font-size:0.85rem;color:#6B7280;">{{ __("L'actu IA en 5 minutes par semaine") }}</div>
                                </div>
                            </a>
                        </div>
                        @endif
                    </div>
                </div>
                @endif

                {{-- CTA infolettre --}}
                @include('fronttheme::partials.tools-newsletter-cta', ['toolSource' => 'lien-expire'])

                {{-- Boutons d'action --}}
                <div style="display:flex;justify-content:center;gap:12px;flex-wrap:wrap;margin-top:32px;margin-bottom:40px;">
                    <a href="{{ route('home') }}" class="ct-btn ct-btn-outline" style="border-radius:var(--r-btn);padding:10px 24px;font-weight:600;">
                        {{ __("Retour à l'accueil") }}
                    </a>
                    <a href="/outils/code-qr" class="ct-btn ct-btn-primary" style="border-radius:var(--r-btn);padding:10px 24px;font-weight:600;">
                        {{ __('Créer un nouveau QR code') }}
                    </a>
                    @if(Route::has('shorturl.create'))
                    <a href="{{ route('shorturl.create') }}" class="ct-btn ct-btn-outline" style="border-radius:var(--r-btn);padding:10px 24px;font-weight:600;">
                        {{ __('Raccourcir un lien') }}
                    </a>
                    @endif
                </div>

            </div>
        </div>
    </div>
</section>
@endsection
