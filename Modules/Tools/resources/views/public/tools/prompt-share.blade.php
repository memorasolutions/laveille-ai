<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('fronttheme::layouts.master')

{{-- Phase 1 permalien public (2026-08-05) : page de partage individuelle d'UN prompt (contenu
     généré par une personne), pas une page de destination éditoriale du site - même politique
     noindex que les autres vues privées/générées par les utilisateurs (Décido, Mes prompts). --}}
@section('page_noindex', true)
@section('title', $pageTitle . ' - ' . config('app.name'))
@section('meta_description', $pageDescription)
@section('og_type', 'article')

@section('content')
@php
    $shareUrl = url('/p/'.$prompt->public_id);
    $remixUrl = url('/outils/constructeur-prompts').'?remix='.$prompt->public_id;
    $linkedinShareUrl = 'https://www.linkedin.com/sharing/share-offsite/?url='.urlencode($shareUrl);
    $twitterShareText = __('Découvrez ce prompt créé avec le constructeur de prompts de laveille.ai :').' '.$prompt->name;
    $twitterShareUrl = 'https://twitter.com/intent/tweet?url='.urlencode($shareUrl).'&text='.urlencode($twitterShareText);
@endphp
<section class="wpo-blog-single-section section-padding">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8 col-12">
                <div class="card shadow-sm" style="border-radius: var(--r-base);" x-data="{}">
                    <div class="card-body p-4 p-md-5">
                        <h1 style="font-family: var(--f-heading); font-weight: 800; color: var(--c-dark); margin: 0 0 0.5rem;">{{ $prompt->name }}</h1>
                        <p class="text-muted mb-4">
                            {{ __('Prompt partagé publiquement, créé avec le') }}
                            <a href="{{ route('tools.show', ['slug' => 'constructeur-prompts']) }}" style="color: var(--c-primary); font-weight: 600; text-decoration: underline;">{{ __('constructeur de prompts') }}</a>
                            {{ __('de laveille.ai.') }}
                        </p>

                        {{-- Avertissement PII, visible AVANT toute action de partage (demande explicite
                             du plan) - même ton que l'avertissement de l'anonymiseur ailleurs sur le site. --}}
                        <div class="p-3 rounded mb-4" role="note" style="background: #FEF3C7; border-left: 3px solid #92400E; border-radius: 8px; font-size: 0.85rem; color: #5b4a1f;">
                            🔒 {{ __('Ce lien est accessible à quiconque le possède. Ne partage jamais de renseignements personnels dans un prompt que tu rends public.') }}
                        </div>

                        <h2 style="font-family: var(--f-heading); font-weight: 700; color: var(--c-dark); font-size: 1rem; margin: 0 0 0.5rem;">{{ __('Le prompt') }}</h2>
                        <pre class="p-3 rounded mb-4" style="background: var(--c-primary-light); white-space: pre-wrap; word-break: break-word; font-family: monospace; font-size: 0.9rem; line-height: 1.6; color: var(--c-dark); margin: 0 0 1.5rem; border: 1px solid rgba(11,114,133,0.15);">{{ $prompt->prompt_text }}</pre>

                        <div class="d-flex flex-wrap gap-2 mb-4">
                            <a href="{{ $remixUrl }}" class="ct-btn ct-btn-primary" style="min-height:44px;">
                                {{ __('Remixer dans le constructeur') }}
                            </a>
                            {{-- Composant DRY "Copier + toast" site-wide (window.copyToClipboard +
                                 window.toast, Modules/FrontTheme/resources/views/layouts/master.blade.php)
                                 - jamais un bouton copier recodé localement. --}}
                            <button type="button" class="ct-btn ct-btn-outline" style="min-height:44px;"
                                    @click="window.copyToClipboard({{ \Illuminate\Support\Js::from($prompt->prompt_text) }}, {{ \Illuminate\Support\Js::from(__('Prompt copié dans le presse-papiers.')) }})">
                                {{ __('Copier le prompt') }}
                            </button>
                        </div>

                        <div class="d-flex align-items-center gap-2 flex-wrap pt-3" style="border-top: 1px solid #E5E7EB; font-size: 0.85rem; color: var(--c-text-muted);">
                            <span class="fw-medium">{{ __('Partager ce lien :') }}</span>
                            <a href="{{ $linkedinShareUrl }}" target="_blank" rel="noopener noreferrer nofollow" class="ct-btn ct-btn-ghost ct-btn-sm" style="min-height:44px;">
                                {{ __('LinkedIn') }}
                            </a>
                            <a href="{{ $twitterShareUrl }}" target="_blank" rel="noopener noreferrer nofollow" class="ct-btn ct-btn-ghost ct-btn-sm" style="min-height:44px;">
                                X (Twitter)
                            </a>
                            <button type="button" class="ct-btn ct-btn-ghost ct-btn-sm" style="min-height:44px;"
                                    @click="window.copyToClipboard({{ \Illuminate\Support\Js::from($shareUrl) }}, {{ \Illuminate\Support\Js::from(__('Lien copié dans le presse-papiers.')) }})">
                                {{ __('Copier le lien') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
