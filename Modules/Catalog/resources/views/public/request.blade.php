<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends(fronttheme_layout())

@section('title', __('Demander un cours personnalisé').' - '.config('app.name'))

@section('page_noindex', true)

@section('breadcrumb')
    @include('fronttheme::partials.breadcrumb', ['breadcrumbTitle' => __('Demander un cours personnalisé')])
@endsection

@push('styles')
<style>
    .courroi { padding: 28px 0 40px; background: #fff; color: #111827; }
    .courroi h1 { color: #111827; font-size: 28px; margin-bottom: 8px; }
    .courroi .lead { color: #374151; font-size: 16px; max-width: 680px; margin-bottom: 8px; }
    .courroi .form-card { max-width: 720px; background: #fff; border: 1px solid #e5e7eb; border-radius: 14px; padding: 24px; box-shadow: 0 1px 2px #0000000d; }
    .courroi label { font-weight: 600; font-size: 14px; color: #111827; display: block; margin-bottom: 6px; }
    .courroi .req { color: #b91c1c; }
    .courroi .opt { color: #6b7280; font-weight: 400; font-size: 12px; }
    .courroi .field { margin-bottom: 16px; }
    .courroi input[type=text], .courroi input[type=email], .courroi input[type=number], .courroi select, .courroi textarea {
        width: 100%; border: 1px solid #cbd5e1; border-radius: 8px; padding: 10px 12px; font-size: 15px; min-height: 44px; box-shadow: none; background: #fff; color: #111827;
    }
    .courroi textarea { min-height: 140px; resize: vertical; }
    .courroi input:focus, .courroi select:focus, .courroi textarea:focus { outline: 3px solid #134e4a; outline-offset: 1px; border-color: #134e4a; }
    .courroi .err { color: #b91c1c; font-size: 13px; margin-top: 4px; }
    .courroi .two { display: grid; grid-template-columns: 1fr; gap: 16px; }
    @media (min-width: 640px) { .courroi .two { grid-template-columns: 1fr 1fr; } }
    .courroi .consent { display: flex; gap: 10px; align-items: flex-start; background: #f0fdfa; border: 1px solid #99f6e4; border-radius: 10px; padding: 12px 14px; margin: 4px 0 18px; }
    /* Le thème porte une regle globale qui MASQUE input[type=checkbox] (display:none) et dessine
       une fausse case via label::before (content:"✔"). Sans l'ecraser, la case native disparait
       et le "✔" du pseudo flotte hors du cadre. On force la case native dans le flux (display +
       appearance), MEME remede que le modal infolettre, ET on neutralise la fausse case du thème
       pour ne pas en afficher deux. */
    .courroi .consent input[type="checkbox"] {
        display: inline-block !important; position: static !important;
        appearance: auto !important; -webkit-appearance: checkbox !important;
        opacity: 1 !important; width: 20px !important; height: 20px !important; min-width: 20px;
        margin: 2px 0 0 !important; flex: 0 0 auto; pointer-events: auto !important;
    }
    .courroi .consent label::before,
    .courroi .consent label::after { content: none !important; display: none !important; }
    .courroi .consent label { font-weight: 400; font-size: 13px; color: #334155; margin: 0; }
    .courroi .btn-send { background: #134e4a; color: #fff; border: none; border-radius: 8px; padding: 12px 22px; font-size: 16px; font-weight: 600; cursor: pointer; min-height: 48px; }
    .courroi .btn-send:hover { background: #0f3d3a; }
    .courroi .alert-ok { background: #ecfdf5; border: 1px solid #6ee7b7; color: #065f46; border-radius: 10px; padding: 14px 16px; margin-bottom: 18px; }
    .courroi .alert-err { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; border-radius: 10px; padding: 14px 16px; margin-bottom: 18px; }
    .courroi .alert-err ul { margin: 6px 0 0; padding-left: 18px; }
</style>
@endpush

@section('content')
<div class="courroi">
    <div class="container">
        <h1>{{ __('Demander un cours personnalisé') }}</h1>
        <p class="lead">{{ __('Vous cherchez une formation sur un sujet précis, ou adaptée à votre équipe? Dites-nous ce dont vous avez besoin et nous reviendrons vers vous avec une proposition.') }}</p>

        <div class="form-card">
            @if(session('success'))
                <div class="alert-ok" role="alert">{{ session('success') }}</div>
            @endif

            @if($errors->any())
                <div class="alert-err" role="alert">
                    {{ __('Votre demande n\'a pas pu être envoyée. Veuillez corriger ce qui suit :') }}
                    <ul>
                        @foreach($errors->all() as $erreur)
                            <li>{{ $erreur }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('catalog.request.store') }}" novalidate>
                @csrf

                {{-- Pot de miel + piège temporel (anti-robot). Invisibles pour un humain. --}}
                <x-core::honeypot />
                {{-- (int) : si un robot renvoie un tableau (form_rendered_at[]=1), old() le restituerait
                     et e() lèverait une erreur au réaffichage ; le cast le ramène à un entier sûr. --}}
                <input type="hidden" name="form_rendered_at" value="{{ (int) old('form_rendered_at', time()) }}">

                <div class="two">
                    <div class="field">
                        <label for="nom">{{ __('Votre nom') }} <span class="req" aria-hidden="true">*</span></label>
                        <input type="text" id="nom" name="nom" value="{{ old('nom') }}" required maxlength="150" autocomplete="name">
                        @error('nom') <div class="err">{{ $message }}</div> @enderror
                    </div>
                    <div class="field">
                        <label for="courriel">{{ __('Votre courriel') }} <span class="req" aria-hidden="true">*</span></label>
                        <input type="email" id="courriel" name="courriel" value="{{ old('courriel') }}" required maxlength="190" autocomplete="email">
                        @error('courriel') <div class="err">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="field">
                    <label for="organisation">{{ __('Organisation') }} <span class="opt">{{ __('(facultatif)') }}</span></label>
                    <input type="text" id="organisation" name="organisation" value="{{ old('organisation') }}" maxlength="190" autocomplete="organization">
                    @error('organisation') <div class="err">{{ $message }}</div> @enderror
                </div>

                <div class="field">
                    <label for="sujet">{{ __('Sujet de la formation souhaitée') }} <span class="req" aria-hidden="true">*</span></label>
                    <input type="text" id="sujet" name="sujet" value="{{ old('sujet') }}" required maxlength="190" placeholder="{{ __('Ex. : intégrer l\'IA dans le travail de bureau') }}">
                    @error('sujet') <div class="err">{{ $message }}</div> @enderror
                </div>

                <div class="two">
                    <div class="field">
                        <label for="heures">{{ __('Durée souhaitée, en heures') }} <span class="opt">{{ __('(facultatif)') }}</span></label>
                        <input type="number" id="heures" name="heures" value="{{ old('heures') }}" min="1" max="500" inputmode="numeric">
                        @error('heures') <div class="err">{{ $message }}</div> @enderror
                    </div>
                    <div class="field">
                        <label for="participants">{{ __('Nombre de participants') }} <span class="opt">{{ __('(facultatif)') }}</span></label>
                        <input type="number" id="participants" name="participants" value="{{ old('participants') }}" min="1" max="1000" inputmode="numeric">
                        @error('participants') <div class="err">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="field">
                    <label for="niveau">{{ __('Niveau visé') }} <span class="opt">{{ __('(facultatif)') }}</span></label>
                    <select id="niveau" name="niveau">
                        <option value="">{{ __('Peu importe / à déterminer') }}</option>
                        <option value="debutant" @selected(old('niveau') === 'debutant')>{{ __('Débutant') }}</option>
                        <option value="intermediaire" @selected(old('niveau') === 'intermediaire')>{{ __('Intermédiaire') }}</option>
                        <option value="avance" @selected(old('niveau') === 'avance')>{{ __('Avancé') }}</option>
                    </select>
                    @error('niveau') <div class="err">{{ $message }}</div> @enderror
                </div>

                <div class="field">
                    <label for="message">{{ __('Décrivez votre besoin') }} <span class="req" aria-hidden="true">*</span></label>
                    <textarea id="message" name="message" required minlength="10" maxlength="5000" placeholder="{{ __('Objectifs, public visé, contraintes d\'horaire, format souhaité (en ligne, en personne)...') }}">{{ old('message') }}</textarea>
                    @error('message') <div class="err">{{ $message }}</div> @enderror
                </div>

                @if(config('services.turnstile.site_key'))
                    <div class="field">
                        <div class="cf-turnstile" data-sitekey="{{ config('services.turnstile.site_key') }}" data-theme="light"></div>
                        @error('cf-turnstile-response') <div class="err">{{ $message }}</div> @enderror
                    </div>
                @endif

                <div class="consent">
                    <input type="checkbox" id="consentement" name="consentement" value="1" required @checked(old('consentement'))>
                    <label for="consentement">{{ __('J\'accepte que MEMORA solutions utilise mon nom et mon courriel dans le seul but de répondre à cette demande de formation. Ces renseignements ne sont ni vendus ni partagés et sont conservés le temps nécessaire au suivi de ma demande; je peux en demander le retrait en tout temps en écrivant à info@memora.ca. Des données techniques (adresse IP) peuvent aussi être conservées à des fins de sécurité.') }}</label>
                </div>
                @error('consentement') <div class="err" style="margin-top:-12px;margin-bottom:14px;">{{ $message }}</div> @enderror

                <button type="submit" class="btn-send">{{ __('Envoyer ma demande') }}</button>
            </form>
        </div>
    </div>
</div>
@endsection

@if(config('services.turnstile.site_key'))
@push('scripts')
<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
@endpush
@endif
