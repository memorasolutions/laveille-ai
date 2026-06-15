@props([
    'source' => null,
    'layout' => 'stacked',
    'submitLabel' => null,
    'showNote' => true,
    'heading' => null,
    'intro' => null,
])

@if($heading)
    <h3>{{ $heading }}</h3>
@endif

@if($intro)
    <p>{{ $intro }}</p>
@endif

<form action="{{ route('newsletter.subscribe') }}" method="POST" {{ $attributes }}>
    @csrf
    <x-honeypot />
    @if(config('services.turnstile.site_key'))<div class="cf-turnstile" data-sitekey="{{ config('services.turnstile.site_key') }}" data-size="invisible" data-action="newsletter"></div>@once @push('scripts')<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>@endpush @endonce @endif

    @if($source)
        <input type="hidden" name="source" value="{{ $source }}">
    @endif

    <div style="{{ $layout === 'inline' ? 'display:flex; gap:8px; flex-wrap:wrap; align-items:center;' : 'display:flex; flex-direction:column; gap:10px;' }}">
        <input
            type="email"
            name="email"
            required
            autocomplete="email"
            aria-label="{{ __('Votre adresse courriel') }}"
            placeholder="{{ __('Votre courriel') }}"
            style="padding:10px 12px; border:1px solid var(--sys-border-default,#D1D5DB); border-radius:var(--sys-radius-sm,6px); font-size:0.95rem; {{ $layout === 'inline' ? 'flex:1; min-width:200px;' : 'width:100%;' }}"
        >

        <x-core::button type="submit" variant="accent" :block="$layout === 'stacked'">
            {{ $submitLabel ?? __('S’inscrire') }}
        </x-core::button>
    </div>
</form>

@if($showNote)
    <p style="font-size:0.75rem; color:var(--sys-text-muted,#52586a); margin-top:12px;">
        {{ __('Double opt-in. Loi 25 / RGPD. Désabonnement 1-clic.') }}
    </p>
@endif
