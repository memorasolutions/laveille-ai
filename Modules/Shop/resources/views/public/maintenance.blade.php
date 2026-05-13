@extends(fronttheme_layout())

@section('title', __('Boutique en maintenance') . ' · ' . config('app.name'))

@push('robots')
<meta name="robots" content="noindex, nofollow">
@endpush

@section('content')
<style>
.sp-maint { padding: 4rem 1rem; background: linear-gradient(135deg, #fff 0%, var(--c-surface, #F8FAFB) 100%); min-height: 60vh; display: flex; align-items: center; justify-content: center; }
.sp-maint__card { max-width: 620px; width: 100%; background: #fff; border: 1px solid var(--c-border, #E5E7EB); border-top: 5px solid var(--c-accent, #C2410C); border-radius: 14px; padding: 2.5rem 2rem; text-align: center; box-shadow: 0 10px 30px rgba(11,114,133,.08); }
.sp-maint__mascot { width: 160px; height: 160px; margin: 0 auto 1.25rem; display: block; }
.sp-maint__mascot .octopus-mascot { display: block; margin: 0 auto; }
.sp-maint__code { font-size: .8rem; font-weight: 700; color: var(--c-text-muted, #52586a); text-transform: uppercase; letter-spacing: 1.5px; margin: 0 0 .5rem; font-family: var(--f-heading, 'Plus Jakarta Sans', sans-serif); }
.sp-maint__title { font-family: var(--f-heading, 'Plus Jakarta Sans', sans-serif); font-size: 1.85rem; font-weight: 800; color: var(--c-dark, #1A1D23); margin: 0 0 .85rem; letter-spacing: -.5px; line-height: 1.2; }
.sp-maint__desc { color: var(--c-text-secondary, #4a4f5c); font-size: 1.05rem; margin: 0 0 1.5rem; line-height: 1.55; }
.sp-maint__desc strong { color: var(--c-accent, #C2410C); }
.sp-maint__actions { display: flex; flex-wrap: wrap; gap: .65rem; justify-content: center; }
.sp-maint__btn { display: inline-flex; align-items: center; gap: .4rem; padding: .75rem 1.4rem; min-height: 44px; border-radius: 8px; font-weight: 700; font-size: .95rem; text-decoration: none; border: 2px solid transparent; transition: background .15s ease, border-color .15s ease; font-family: var(--f-heading, 'Plus Jakarta Sans', sans-serif); }
.sp-maint__btn--primary { background: var(--c-primary, #0B7285); color: #fff; border-color: var(--c-primary, #0B7285); }
.sp-maint__btn--primary:hover { background: var(--c-primary-hover, #064E5C); color: #fff; }
.sp-maint__btn--secondary { background: var(--c-chip-bg, #ECEEF2); color: var(--c-dark, #1A1D23); border-color: var(--c-chip-bg, #ECEEF2); }
.sp-maint__btn--secondary:hover { background: #D5D9E0; }
@media (max-width: 540px) {
    .sp-maint { padding: 3rem .85rem; }
    .sp-maint__card { padding: 2rem 1.25rem; }
    .sp-maint__title { font-size: 1.5rem; }
    .sp-maint__mascot { width: 120px; height: 120px; }
}
</style>

<section class="sp-maint" role="main">
    <div class="sp-maint__card">
        <div class="sp-maint__mascot" aria-hidden="true">
            <x-tools::octopus variant="thinking" :size="160" />
        </div>
        <p class="sp-maint__code">{{ __('Boutique') }} · {{ __('Maintenance') }}</p>
        <h1 class="sp-maint__title">{{ __('Octopus prépare la nouvelle boutique') }}</h1>
        <p class="sp-maint__desc">
            {{ __('Nous sommes en train de bonifier la boutique pour t\'offrir plus de couleurs, plus de tailles et des mockups fidèles aux produits que tu recevras.') }}
            <br><br>
            <strong>{{ __('Reviens très bientôt.') }}</strong>
        </p>
        <div class="sp-maint__actions">
            <a href="{{ url('/') }}" class="sp-maint__btn sp-maint__btn--primary">{{ __('Retour à l\'accueil') }}</a>
            <a href="{{ url('/outils') }}" class="sp-maint__btn sp-maint__btn--secondary">{{ __('Explorer les outils') }}</a>
        </div>
    </div>
</section>
@endsection
