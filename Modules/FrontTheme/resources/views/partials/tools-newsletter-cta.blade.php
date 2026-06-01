{{-- Rétention (diag 2026-06-01) : les outils sont l'aimant #1 du site. CTA infolettre discret,
     non intrusif, en bas d'un outil — capte le visiteur le plus engagé. $toolSource = source de suivi. --}}
@php $lvToolSource = $toolSource ?? 'outil'; @endphp
<section aria-label="{{ __('Infolettre') }}" style="max-width:680px;margin:48px auto 8px;padding:24px;border:1px solid var(--sys-border-subtle, #E5E7EB);border-radius:12px;background:var(--sys-surface-raised, #F8FAFB);">
    <h2 style="font-family: var(--f-heading); color: var(--sys-text-default, #1A1D23); font-size:1.15rem; margin:0 0 8px;">{{ __('Cet outil vous a été utile ?') }}</h2>
    <p style="color: var(--sys-text-default, #1A1D23); margin:0 0 16px; font-size:0.95rem;">{{ __("Recevez chaque semaine notre concentré de l'actualité IA et nos nouveaux outils — sans bruit, en quelques minutes de lecture.") }}</p>
    <x-fronttheme::newsletter-form :source="'outil-' . $lvToolSource" layout="inline" :show-note="true" />
</section>
