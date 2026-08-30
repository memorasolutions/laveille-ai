<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends(fronttheme_layout())
@php $shareData = $tool->getShareData(); @endphp
@section('meta_description', $shareData['meta_description'])
@section('og_type', $shareData['og_type'])
@section('og_image', $shareData['og_image'])
@section('share_text', $shareData['share_text'])
@section('title', $tool->name . ' - ' . config('app.name'))
@section('meta_description', 'Calculatrice de taxes canadienne. TPS, TVQ, TVP, TVH pour toutes les provinces. Pourboire et division de facture inclus.')
@section('breadcrumb')
    @include('fronttheme::partials.breadcrumb', ['breadcrumbTitle' => $tool->name, 'breadcrumbItems' => [__('Outils'), $tool->name]])
@endsection

@push('styles')
<link href="{{ asset('tools/calculatrice/css/app.css') }}" rel="stylesheet">
<link href="{{ asset('tools/calculatrice/css/clean-layout.css') }}" rel="stylesheet">
<link href="{{ asset('tools/calculatrice/css/tip-popup.css') }}" rel="stylesheet">
<style>
    .calculator-app { max-width: 100%; }
    .calculator-app .app-header, .calculator-app .app-footer { display: none; }
    .calculator-app .card { box-shadow: none; border: none; }
    .calculator-app .province-select { padding-right: 2.5rem !important; background-position: right 1rem center !important; }
    .calculator-app .calculator-grid { display: flex !important; flex-direction: column !important; gap: 1rem; }
    .calculator-app .tax-display-group { display: flex !important; gap: 1rem; flex-wrap: wrap; background: #f8f9fa; border-radius: 8px; padding: 1rem; }
    .calculator-app .tax-display-group .form-group { flex: 1; min-width: 120px; }
    .calculator-app .tax-placeholder { display: none !important; }
    .calculator-app .input-wrapper { display: flex; align-items: center; gap: 0.5rem; }
    .calculator-app .amount-input, .calculator-app .readonly-input { width: 100% !important; min-width: 0; }
    .calculator-app label { font-weight: 600; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.03em; color: #555; margin-bottom: 0.3rem; display: block; }
    .calculator-app .province-select { width: 100% !important; }
    .calculator-app .main-calculator { padding: 0 !important; }
    .calculator-app .form-group { margin-bottom: 0.5rem !important; }
    .calculator-app .calculator-grid { gap: 0.5rem !important; }
    .calculator-app .tax-display-group { padding: 0.75rem !important; margin: 0 !important; }
    .calculator-app .section-divider { margin: 0.5rem 0 !important; }
    .calculator-app .tip-section, .calculator-app .split-section, .calculator-app .actions-section { margin-top: 0 !important; padding-top: 0 !important; }
    .calculator-app .split-section h2 { margin-top: 0; font-size: 1rem; }
    .calculator-app .card-body { padding: 1.5rem !important; }
    @media (max-width: 576px) {
        .calculator-app .tax-display-group { flex-direction: row !important; }
        .calculator-app .tax-display-group .form-group { min-width: 0; flex: 1; }
        .calculator-app .card-body { padding: 1rem !important; }
        .quick-amounts { justify-content: center; gap: 0.25rem !important; margin-bottom: 0.5rem !important; }
        .quick-amounts span { display: none !important; }
        /* #P0-audit 2026-08-30 : min-height 44px conservé même à 390px - la cible tactile WCAG 2.2
           AAA (44x44 px) ne se rétrécit pas sous prétexte de manque de place ; on réduit le
           padding horizontal, jamais la hauteur. */
        .quick-amt-btn { padding: 3px 10px !important; font-size: 0.8rem !important; min-height: 44px !important; }
    }
</style>
@endpush

@section('content')
<section class="wpo-blog-single-section section-padding">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-10 col-12">
                {{-- #707 : tool-geo (JSON-LD + answer-box) déplacé DANS le conteneur pour respecter
                     la largeur du contenu (auparavant plein-largeur hors .container, cf. blog/show.blade.php). --}}
                @include('tools::public.partials.tool-geo')
                <div class="card shadow-sm tool-fullscreen-target" style="border-radius: var(--r-base);">
                    <div class="card-body p-4 p-md-5">
                        <div class="d-flex justify-content-between align-items-start mb-1">
                            <h1 style="font-family: var(--f-heading); font-weight: 800; color: var(--c-dark); margin: 0;">{{ $tool->name }}</h1>
                            <div class="d-flex gap-1">
                                @include('tools::partials.fullscreen-btn')
                                @include('tools::partials.share-btn', ['tool' => $tool])
                            </div>
                        </div>
                        <p class="text-muted mb-4">{{ __('Calculez les taxes de vente (TPS, TVQ, TVP, TVH) pour toutes les provinces et territoires du Canada. Inclut pourboire et division de facture.') }}</p>

                        {{-- Embedded calculator from original tool --}}
                        <div class="calculator-app">
                            <main>
                                <section class="main-calculator card">
                                    <div class="form-group">
                                        <label for="province">
                                            {{ __('Province / Territoire') }}
                                            <button type="button" class="ct-help-btn" data-help-key="province" aria-label="Aide Province">ⓘ</button>
                                        </label>
                                        <select id="province" aria-label="Province" class="province-select">
                                            <option value="">{{ __('Sélectionnez une province') }}</option>
                                            <option value="QC" data-gst="5" data-pst="0" data-qst="9.975" data-hst="0" selected>Québec (14,975 %)</option>
                                            <option value="ON" data-gst="0" data-pst="0" data-qst="0" data-hst="13">Ontario (13 %)</option>
                                            <option value="AB" data-gst="5" data-pst="0" data-qst="0" data-hst="0">Alberta (5 %)</option>
                                            <option value="BC" data-gst="5" data-pst="7" data-qst="0" data-hst="0">Colombie-Britannique (12 %)</option>
                                            <option value="MB" data-gst="5" data-pst="7" data-qst="0" data-hst="0">Manitoba (12 %)</option>
                                            <option value="NB" data-gst="0" data-pst="0" data-qst="0" data-hst="15">Nouveau-Brunswick (15 %)</option>
                                            <option value="NL" data-gst="0" data-pst="0" data-qst="0" data-hst="15">Terre-Neuve-et-Labrador (15 %)</option>
                                            <option value="NS" data-gst="0" data-pst="0" data-qst="0" data-hst="14">Nouvelle-Écosse (14 %)</option>
                                            <option value="PE" data-gst="0" data-pst="0" data-qst="0" data-hst="15">Île-du-Prince-Édouard (15 %)</option>
                                            <option value="SK" data-gst="5" data-pst="6" data-qst="0" data-hst="0">Saskatchewan (11 %)</option>
                                            <option value="NT" data-gst="5" data-pst="0" data-qst="0" data-hst="0">Territoires du Nord-Ouest (5 %)</option>
                                            <option value="NU" data-gst="5" data-pst="0" data-qst="0" data-hst="0">Nunavut (5 %)</option>
                                            <option value="YT" data-gst="5" data-pst="0" data-qst="0" data-hst="0">Yukon (5 %)</option>
                                        </select>
                                    </div>

                                    {{-- #16 S84 v3 : Bidirectionnel natif — saisi dans n'importe quel champ → autre se calcule automatiquement --}}
                                    <p style="font-size: 0.85rem; color: var(--c-text-muted, #52586a); margin: 0 0 0.75rem 0; padding: 0.5rem 0.75rem; background: #f1f3f5; border-radius: 8px; border-left: 3px solid var(--c-primary, #064E5A); max-width: 65ch;">
                                        💡 Saisissez le montant <strong>avant</strong> OU <strong>avec taxes</strong> – l'autre champ se calcule automatiquement.
                                    </p>

                                    {{-- Montants rapides --}}
                    <div class="quick-amounts" style="display: flex; gap: 0.5rem; flex-wrap: wrap; margin-bottom: 1rem;">
                        <span style="font-size: 0.85rem; color: var(--c-text-muted, #52586a); align-self: center;">{{ __('Montants rapides :') }}</span>
                        <button type="button" class="quick-amt-btn" data-amount="10" style="padding: 4px 12px; min-height: 44px; border: 1px solid #ddd; border-radius: 20px; background: #fff; cursor: pointer; font-size: 0.85rem;">10 $</button>
                        <button type="button" class="quick-amt-btn" data-amount="25" style="padding: 4px 12px; min-height: 44px; border: 1px solid #ddd; border-radius: 20px; background: #fff; cursor: pointer; font-size: 0.85rem;">25 $</button>
                        <button type="button" class="quick-amt-btn" data-amount="50" style="padding: 4px 12px; min-height: 44px; border: 1px solid #ddd; border-radius: 20px; background: #fff; cursor: pointer; font-size: 0.85rem;">50 $</button>
                        <button type="button" class="quick-amt-btn" data-amount="100" style="padding: 4px 12px; min-height: 44px; border: 1px solid #ddd; border-radius: 20px; background: #fff; cursor: pointer; font-size: 0.85rem;">100 $</button>
                        <button type="button" class="quick-amt-btn" data-amount="500" style="padding: 4px 12px; min-height: 44px; border: 1px solid #ddd; border-radius: 20px; background: #fff; cursor: pointer; font-size: 0.85rem;">500 $</button>
                    </div>

                    <div class="calculator-grid">
                                        <div class="form-group">
                                            <label for="amount-before-tax">
                                                {{ __('Montant avant taxes') }}
                                                <button type="button" class="ct-help-btn" data-help-key="avant_taxes" aria-label="Aide Montant avant taxes">ⓘ</button>
                                            </label>
                                            <div class="input-wrapper">
                                                <span class="currency-symbol">$</span>
                                                {{-- #P0-audit 2026-08-30 : type="text" + inputmode="decimal", jamais type="number". Un
                                                     <input type="number"> REJETTE la virgule française au clavier - confirmé par frappe
                                                     réelle : taper "12,50" au clavier donne une valeur DOM "1250" (la virgule est avalée en
                                                     silence, 100x l'erreur). window.CalcParseAmount (défini plus bas) gère virgule/point. --}}
                                                <input type="text" id="amount-before-tax" aria-label="Montant avant taxes" placeholder="0,00" inputmode="decimal" autocomplete="off" class="amount-input">
                                            </div>
                                        </div>

                                        <div class="tax-display-group">
                                            <div class="tax-placeholder" id="tax-placeholder" style="display:none;">
                                                <p>{{ __('Choisir votre province') }}</p>
                                            </div>
                                            <div class="form-group" id="tax1-group">
                                                <label id="tax1-label">TPS (5 %)</label>
                                                <div class="input-wrapper">
                                                    <span class="currency-symbol">$</span>
                                                    <input type="text" id="tax1-amount" readonly class="readonly-input" value="0,00" aria-labelledby="tax1-label">
                                                </div>
                                            </div>
                                            <div class="form-group" id="tax2-group">
                                                <label id="tax2-label">TVQ (9,975 %)</label>
                                                <div class="input-wrapper">
                                                    <span class="currency-symbol">$</span>
                                                    <input type="text" id="tax2-amount" readonly class="readonly-input" value="0,00" aria-labelledby="tax2-label">
                                                </div>
                                            </div>
                                        </div>

                                        <div class="form-group">
                                            <label for="amount-after-tax">
                                                {{ __('Montant avec taxes') }}
                                                <button type="button" class="ct-help-btn" data-help-key="avec_taxes" aria-label="Aide Montant avec taxes">ⓘ</button>
                                            </label>
                                            <div class="input-wrapper">
                                                <span class="currency-symbol">$</span>
                                                <input type="text" id="amount-after-tax" aria-label="Montant après taxes" placeholder="0,00" inputmode="decimal" autocomplete="off" class="amount-input total-amount">
                                            </div>
                                        </div>
                                    </div>

                                    {{-- #16 S84 v3 : Toggle pourboire toujours visible — actif si user saisit dans 'Montant avec taxes' --}}
                                    <div class="ct-tip-toggle-wrapper" id="ct-tip-toggle-wrapper" style="margin-bottom: 0.5rem;">
                                        <button type="button" id="ct-tip-toggle-btn" class="ct-btn ct-btn-outline" aria-expanded="false" aria-controls="ct-tip-options" style="width: 100%; text-align: left; padding: 0.6rem 0.9rem; min-height: 44px; display: flex; justify-content: space-between; align-items: center;">
                                            <span>🍽️ Avec pourboire</span>
                                            <span id="ct-tip-toggle-arrow" style="transition: transform 0.2s; font-size: 0.9rem;">▼</span>
                                        </button>
                                        <div id="ct-tip-options" style="display: none; padding: 0.9rem; background: #f8f9fa; border-radius: 8px; margin-top: 0.4rem; border: 1px solid #e2e6ea;">
                                            <div class="form-group" style="margin-bottom: 0;">
                                                <label for="rt-tip-percent" style="margin-bottom: 0.5rem;">{{ __('Pourcentage du pourboire') }}</label>
                                                <div style="display: flex; gap: 0.4rem; flex-wrap: wrap; align-items: center;">
                                                    <button type="button" class="rt-tip-preset ct-btn ct-btn-outline" data-tip="10" style="padding: 4px 12px; font-size: 0.85rem; min-width: 44px; min-height: 44px;">10 %</button>
                                                    <button type="button" class="rt-tip-preset ct-btn ct-btn-outline" data-tip="15" style="padding: 4px 12px; font-size: 0.85rem; min-width: 44px; min-height: 44px;">15 %</button>
                                                    <button type="button" class="rt-tip-preset ct-btn ct-btn-outline" data-tip="18" style="padding: 4px 12px; font-size: 0.85rem; min-width: 44px; min-height: 44px;">18 %</button>
                                                    <button type="button" class="rt-tip-preset ct-btn ct-btn-outline" data-tip="20" style="padding: 4px 12px; font-size: 0.85rem; min-width: 44px; min-height: 44px;">20 %</button>
                                                    <div class="input-wrapper" style="flex: 1; min-width: 100px; position: relative;">
                                                        <input type="text" id="rt-tip-percent" aria-label="Pourcentage personnalisé" placeholder="{{ __('Personnalisé') }}" inputmode="decimal" autocomplete="off" class="amount-input" style="padding-right: 2rem;">
                                                        <span style="position: absolute; right: 0.6rem; top: 50%; transform: translateY(-50%); color: var(--c-text-muted, #52586a);">%</span>
                                                    </div>
                                                </div>
                                            </div>
                                            {{-- #16 P0-audit 2026-08-30 : choix explicite de la BASE du pourboire, demandé par le
                                                 fondateur - jusqu'ici l'outil devinait selon le champ actif (souvent "après taxes", à
                                                 l'encontre de l'usage québécois). Défaut = avant taxes (usage local). Vrais boutons
                                                 radio (fieldset/legend) : sémantique native, navigables au clavier sans JS additionnel. --}}
                                            <fieldset style="margin: 0.75rem 0 0 0; padding: 0; border: none;">
                                                <legend style="font-size: 0.8rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.03em; color: #555; margin-bottom: 0.4rem;">{{ __('Pourboire calculé sur') }}</legend>
                                                <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                                                    <label style="display: flex; align-items: center; gap: 0.4rem; min-height: 44px; cursor: pointer; font-size: 0.9rem; font-weight: 400; text-transform: none; letter-spacing: normal;">
                                                        <input type="radio" name="ct-tip-base" id="ct-tip-base-before" value="before" checked style="width: 20px; height: 20px; accent-color: var(--c-primary, #064E5A);">
                                                        {{ __('Montant avant taxes (usage courant au Québec)') }}
                                                    </label>
                                                    <label style="display: flex; align-items: center; gap: 0.4rem; min-height: 44px; cursor: pointer; font-size: 0.9rem; font-weight: 400; text-transform: none; letter-spacing: normal;">
                                                        <input type="radio" name="ct-tip-base" id="ct-tip-base-after" value="after" style="width: 20px; height: 20px; accent-color: var(--c-primary, #064E5A);">
                                                        {{ __('Montant avec taxes') }}
                                                    </label>
                                                </div>
                                            </fieldset>
                                            <div id="rt-result" style="display: none; margin-top: 1rem; padding-top: 0.75rem; border-top: 1px solid #e2e6ea; font-size: 0.9rem;" aria-live="polite">
                                                <div style="display: flex; justify-content: space-between; padding: 3px 0;"><span>{{ __('Pourboire') }} (<span id="rt-result-tip-pct">0</span> % <span id="rt-result-base-desc">{{ __('du montant avant taxes') }}</span>)</span><strong id="rt-result-tip-amount">0,00 $</strong></div>
                                                <div style="display: flex; justify-content: space-between; padding: 5px 0; border-top: 1px solid #e2e6ea; margin-top: 5px;"><span id="rt-result-final-label" style="font-weight: 700;">{{ __('Total à payer (avec pourboire)') }}</span><strong id="rt-result-final" style="color: var(--c-primary, #064E5A); font-size: 1.05rem;">0,00 $</strong></div>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- #16 v3-D : Section pourboire popup retirée — toggle inline unique gère les 2 directions --}}

                                    <div class="section-divider" id="split-divider" style="display: none;"></div>

                                    <div class="split-section" id="split-section" style="display: none;">
                                        <h2>{{ __('Diviser la facture') }}</h2>
                                        <div class="form-group">
                                            <label for="people">{{ __('Nombre de personnes') }}</label>
                                            <div class="range-wrapper">
                                                <input type="range" id="people" aria-label="Nombre de personnes" min="1" max="20" value="1">
                                                <span class="range-value">1</span>
                                            </div>
                                        </div>
                                        <div class="split-result" style="display: none;">
                                            <div class="per-person" data-people="1">
                                                <span>{{ __('Par personne') }}</span>
                                                <span class="per-person-amount">0,00 $</span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="section-divider" id="actions-divider" style="display: none;"></div>

                                    <div class="actions-section" id="actions-section" style="display: none;">
                                        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                                            <button id="reset-btn" class="ct-btn ct-btn-outline" style="display: none;">{{ __('Nouveau calcul') }}</button>
                                            <button id="copy-result-btn" class="ct-btn ct-btn-outline" style="display: none;">{{ __('Copier le résultat') }}</button>
                                            {{-- #15 S84 Option A : Partager mon calcul (Web Share API + URL deep-link) --}}
                                            <button id="share-calc-btn" class="ct-btn ct-btn-primary" style="display: none;" title="{{ __('Partage un lien qui recrée ce calcul exact') }}">📤 {{ __('Partager mon calcul') }}</button>
                                            <button id="save-history-btn" class="ct-btn ct-btn-outline" style="display: none;">💾 {{ __('Sauvegarder') }}</button>
                                        </div>
                                    </div>

                                    {{-- Historique --}}
                                    <div id="tax-history-section" style="display: none; margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #eee;">
                                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                                            <h4 style="margin: 0; font-size: 0.95rem; font-weight: 700;">{{ __('Historique récent') }}</h4>
                                            <button id="clear-history-btn" style="background: none; border: none; color: #dc2626; cursor: pointer; font-size: 0.8rem;">{{ __('Effacer') }}</button>
                                        </div>
                                        <div id="tax-history-list"></div>
                                    </div>
                                </section>
                            </main>
                        </div>

                        <p class="text-muted mt-3 mb-0" style="font-size: 0.8rem;">{{ __('Taux mis à jour en 2025. TVQ calculée sur le montant avant taxes. Cet outil est fourni à titre indicatif.') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@include('fronttheme::partials.tools-newsletter-cta', ['toolSource' => 'calculatrice-taxes'])
@endsection

@push('scripts')
@php
    $calcHelp = [
        'province' => [
            'title' => 'ⓘ Province / Territoire',
            'body' => '<p>Choisissez votre province ou territoire canadien. Les taxes varient selon la région :</p>'
                    . '<ul>'
                    . '<li><strong>TPS (5 %) seule</strong> : Alberta, Yukon, Territoires du Nord-Ouest, Nunavut.</li>'
                    . '<li><strong>TPS + TVQ</strong> : Québec (5 % + 9,975 % = 14,975 %).</li>'
                    . '<li><strong>TPS + TVP</strong> : Colombie-Britannique (12 %), Manitoba (12 %), Saskatchewan (11 %).</li>'
                    . '<li><strong>TVH unique</strong> : Ontario (13 %), Nouveau-Brunswick / Terre-Neuve / Île-du-Prince-Édouard (15 %), Nouvelle-Écosse (14 %).</li>'
                    . '</ul>'
                    . '<p style="font-size:0.85rem; color: var(--c-text-muted, #52586a);">Taux mis à jour 2025 (Revenu Canada / Revenu Québec).</p>',
        ],
        'avant_taxes' => [
            'title' => 'ⓘ Montant avant taxes',
            'body' => '<p>Le montant <strong>avant taxes</strong> (sous-total HT) est le prix affiché du produit ou service, <strong>avant ajout</strong> de la TPS, TVQ, TVP ou TVH.</p>'
                    . '<p><strong>Exemple Québec :</strong> vous achetez un article à 100 $ avant taxes. À la caisse, on ajoute 5 $ TPS et 9,98 $ TVQ. Vous payez 114,98 $ au total.</p>'
                    . '<p style="font-size:0.85rem; color: var(--c-text-muted, #52586a);">💡 Saisissez ce montant si vous connaissez le prix avant taxes – l\'autre champ se calcule automatiquement.</p>',
        ],
        'avec_taxes' => [
            'title' => 'ⓘ Montant avec taxes',
            'body' => '<p>Le montant <strong>avec taxes</strong> (TTC) est le total final que vous payez, <strong>incluant</strong> toutes les taxes applicables.</p>'
                    . '<p><strong>Exemple Québec :</strong> vous payez 114,98 $ à la caisse. La calculatrice décompose : 100 $ avant taxes + 5 $ TPS + 9,98 $ TVQ.</p>'
                    . '<p style="font-size:0.85rem; color: var(--c-text-muted, #52586a);">💡 Saisissez ce montant pour faire le calcul inversé – l\'autre champ se calcule automatiquement.</p>'
                    . '<p style="font-size:0.85rem; color: var(--c-text-muted, #52586a);">🍽️ Le pourboire (section « Avec pourboire ») s\'ajoute toujours EN PLUS de ce montant – il ne fait jamais partie du montant que vous saisissez ici.</p>',
        ],
    ];
@endphp
<script>
    window.HELP_CONTENT = {!! json_encode($calcHelp, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!};

    // #P0-audit 2026-08-30 : normalisation de saisie numérique partagée, PLACÉE ICI (avant les
    // scripts de l'outil) pour être réutilisable par d'autres outils de la même famille - aucun
    // utilitaire équivalent n'existait ailleurs dans le projet (vérifié par grep sur public/tools
    // et public/js avant d'écrire celui-ci, conformément à la règle DRY du projet).
    //
    // window.CalcParseAmount(brut) -> nombre JS ou NaN. Gère, dans cet ordre :
    //  - espaces (normaux ET insécables U+00A0/U+202F) utilisés comme séparateur de milliers,
    //    supprimés uniquement s'ils ne sont PAS suivis d'exactement 2 chiffres (sinon ce serait
    //    un espace décimal, cas non standard - on ne devine pas, cf. "1,234.56" plus bas) ;
    //  - un SEUL séparateur décimal, virgule OU point, désigné explicitement (jamais deviné) :
    //    c'est le DERNIER "," ou "." de la chaîne qui fait foi si un chiffre le suit ; tout
    //    séparateur antérieur est alors traité comme un séparateur de milliers et retiré. Couvre
    //    "12,50", "12.50", "1 234,56", "1 234,56" (espace insécable), "1,234.56" (anglais) ET
    //    "1.234,56" (européen) sans ambiguïté puisque c'est la POSITION (dernier séparateur) qui
    //    tranche, jamais une supposition sur la locale ;
    //  - saisie partielle pendant la frappe ("12," ou "12,0") : NE JAMAIS vider ni rejeter, on
    //    complète mentalement le nombre déjà tapé (parseFloat s'arrête proprement).
    window.CalcParseAmount = function (raw) {
        if (raw === null || raw === undefined) return NaN;
        var s = raw.toString().trim();
        if (s === '') return NaN;
        s = s.replace(/[  ]/g, ' '); // espaces insécables -> espace normal
        s = s.replace(/\s/g, ''); // espaces (milliers) retirés - jamais un séparateur décimal en fr/en
        var lastComma = s.lastIndexOf(',');
        var lastDot = s.lastIndexOf('.');
        var lastSep = Math.max(lastComma, lastDot);
        if (lastSep === -1) {
            var n0 = parseFloat(s);
            return isNaN(n0) ? NaN : n0;
        }
        var intPart = s.slice(0, lastSep).replace(/[.,]/g, '') || '0';
        var decPart = s.slice(lastSep + 1).replace(/[^0-9]/g, '');
        var n = parseFloat(intPart + '.' + (decPart === '' ? '0' : decPart));
        return isNaN(n) ? NaN : n;
    };

    // window.CalcMoney(nombre, {withSymbol}) -> chaîne fr-CA : virgule décimale, espace insécable
    // avant le symbole "$" QUAND il est demandé. withSymbol=false pour les champs qui affichent
    // déjà un "$" séparé (span .currency-symbol du gabarit) - évite le double symbole constaté
    // (capture réelle : "$5.00$") quand le "$" du prefix ET celui du formatteur coexistaient.
    window.CalcMoney = function (n, opts) {
        opts = opts || {};
        if (typeof n !== 'number' || isNaN(n)) n = 0;
        var out = n.toFixed(2).replace('.', ',');
        return opts.withSymbol === false ? out : out + ' $';
    };

    window.taxConfig = {
        tax_rates: {
            AB: {name: 'Alberta', gst: 5, pst: 0, total: 5},
            BC: {name: 'Colombie-Britannique', gst: 5, pst: 7, total: 12},
            MB: {name: 'Manitoba', gst: 5, pst: 7, total: 12},
            NB: {name: 'Nouveau-Brunswick', hst: 15, total: 15},
            NL: {name: 'Terre-Neuve-et-Labrador', hst: 15, total: 15},
            NS: {name: 'Nouvelle-Écosse', hst: 14, total: 14},
            NT: {name: 'Territoires du Nord-Ouest', gst: 5, pst: 0, total: 5},
            NU: {name: 'Nunavut', gst: 5, pst: 0, total: 5},
            ON: {name: 'Ontario', hst: 13, total: 13},
            PE: {name: 'Île-du-Prince-Édouard', hst: 15, total: 15},
            QC: {name: 'Québec', gst: 5, qst: 9.975, total: 14.975},
            SK: {name: 'Saskatchewan', gst: 5, pst: 6, total: 11},
            YT: {name: 'Yukon', gst: 5, pst: 0, total: 5}
        },
        app_settings: {
            title: 'Calculatrice de Taxes Canada',
            default_tip_percentages: [10, 15, 18, 20],
            max_people_split: 20,
            currency_symbol: '$',
            decimal_places: 2
        }
    };
</script>
<script src="{{ asset('tools/calculatrice/js/tip-popup.js') }}"></script>
<script src="{{ asset('tools/calculatrice/js/calculator-simple.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var sel = document.getElementById('province');
    if (sel && sel.value) { sel.dispatchEvent(new Event('change', {bubbles: true})); }

    // Montants rapides
    document.querySelectorAll('.quick-amt-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var amt = this.getAttribute('data-amount');
            var input = document.getElementById('amount-before-tax');
            if (input) { input.value = amt; input.dispatchEvent(new Event('input', {bubbles: true})); }
            document.querySelectorAll('.quick-amt-btn').forEach(function(b) { b.style.background = '#fff'; b.style.color = '#333'; });
            this.style.background = '#0B7285'; this.style.color = '#fff';
        });
    });

    // #16 S84 v3 : Bidirectionnel natif via engine activeField. Toggle pourboire optionnel.
    var tipIncluded = false;
    function getActiveField() {
        var st = window.simpleCalculator && window.simpleCalculator.state;
        return st && st.activeField ? st.activeField : 'before';
    }

    // Helper DRY : extrait données calcul actuel du DOM. Modèle UNIFIÉ (#P0-audit 2026-08-30) :
    // sous-total, taxes, TTC et pourboire sont TOUJOURS cohérents simultanément - fini le
    // branchement "extraction" qui pouvait les contredire (ancien bug : le pourboire faisait
    // baisser le sous-total affiché, cf. CHANGELOG).
    function getCalculationData() {
        var province = document.getElementById('province');
        var before = document.getElementById('amount-before-tax');
        var tax1 = document.getElementById('tax1-amount');
        var tax2 = document.getElementById('tax2-amount');
        var after = document.getElementById('amount-after-tax');
        var t1Label = document.getElementById('tax1-label');
        var t2Label = document.getElementById('tax2-label');
        var lines = [];
        if (province && province.value) lines.push('Province : ' + province.options[province.selectedIndex].text);
        if (before && before.value) lines.push('Avant taxes : ' + before.value + ' $');
        if (t1Label && tax1 && tax1.value) lines.push(t1Label.textContent + ' : ' + tax1.value + ' $');
        if (t2Label && tax2 && tax2.value && tax2.value !== '0,00' && tax2.value !== '0.00') lines.push(t2Label.textContent + ' : ' + tax2.value + ' $');
        if (after && after.value) lines.push('Total (avec taxes) : ' + after.value + ' $');
        var tipShown = isTipOpen() && rtResult && rtResult.style.display !== 'none';
        var tipPctEl = document.getElementById('rt-result-tip-pct');
        var tipAmtEl = document.getElementById('rt-result-tip-amount');
        var finalEl = document.getElementById('rt-result-final');
        if (tipShown && tipPctEl && tipAmtEl) lines.push('Pourboire (' + tipPctEl.textContent + ' %, ' + (getTipBase() === 'after' ? 'sur le montant avec taxes' : 'sur le montant avant taxes') + ') : ' + tipAmtEl.textContent);
        if (tipShown && finalEl) lines.push('Total à payer (avec pourboire) : ' + finalEl.textContent);

        var activeField = getActiveField();
        var sourceAmount = activeField === 'after' ? (after ? after.value : '') : (before ? before.value : '');
        return {
            text: lines.join('\n'),
            source: activeField,
            province: province ? province.value : '',
            amount: sourceAmount,
            tip: (tipShown && rtPctEl) ? rtPctEl.value : '',
            tipBase: getTipBase(),
            hasData: !!(province && province.value && sourceAmount)
        };
    }

    // Construire URL deep-link (#15 + #16 v3 + #P0-audit choix de base du pourboire)
    function buildShareUrl(data) {
        var url = new URL(window.location.href);
        ['p','a','m','t','s','tb'].forEach(function(k) { url.searchParams.delete(k); });
        if (data.province) url.searchParams.set('p', data.province);
        if (data.amount) url.searchParams.set('a', data.amount);
        if (data.source === 'after') url.searchParams.set('s', 'after');
        if (data.tip) url.searchParams.set('t', data.tip);
        if (data.tip && data.tipBase === 'after') url.searchParams.set('tb', 'after');
        return url.toString();
    }

    // Copier résultat
    var copyBtn = document.getElementById('copy-result-btn');
    if (copyBtn) {
        copyBtn.style.display = '';
        copyBtn.addEventListener('click', function() {
            var d = getCalculationData();
            navigator.clipboard.writeText(d.text);
            window.toast('{{ __("Résultat copié") }}', 'success', 2000);
            this.textContent = '{{ __("Copié !") }}';
            var self = this;
            setTimeout(function() { self.textContent = '{{ __("Copier le résultat") }}'; }, 2000);
        });
    }

    // #15 S84 Option A : Partager mon calcul (Web Share API + URL deep-link + fallback clipboard)
    var shareCalcBtn = document.getElementById('share-calc-btn');
    if (shareCalcBtn) {
        shareCalcBtn.style.display = '';
        shareCalcBtn.addEventListener('click', function() {
            var d = getCalculationData();
            if (!d.hasData) {
                this.textContent = '⚠️ {{ __("Saisir un montant") }}';
                var self0 = this;
                setTimeout(function() { self0.innerHTML = '📤 {{ __("Partager mon calcul") }}'; }, 2000);
                return;
            }
            var shareUrl = buildShareUrl(d);
            var shareData = {
                title: '{{ __("Estimation des taxes") }} : {{ config("app.name") }}',
                text: d.text,
                url: shareUrl
            };
            var self = this;
            var resetLabel = function() {
                setTimeout(function() { self.innerHTML = '📤 {{ __("Partager mon calcul") }}'; }, 2500);
            };
            // Détection support Web Share API (mobile principalement)
            if (navigator.share && navigator.canShare && navigator.canShare(shareData)) {
                navigator.share(shareData)
                    .then(function() {
                        self.textContent = '✓ {{ __("Partagé !") }}';
                        resetLabel();
                    })
                    .catch(function(err) {
                        if (err && err.name === 'AbortError') return; // user cancelled
                        // Fallback clipboard
                        navigator.clipboard.writeText(d.text + '\n\n' + shareUrl);
                        window.toast('{{ __("Lien copié") }}', 'success', 2000);
                        self.textContent = '🔗 {{ __("Lien copié !") }}';
                        resetLabel();
                    });
            } else {
                // Fallback desktop : copy résumé + URL clipboard
                navigator.clipboard.writeText(d.text + '\n\n' + shareUrl);
                window.toast('{{ __("Lien copié") }}', 'success', 2000);
                self.textContent = '🔗 {{ __("Lien copié !") }}';
                resetLabel();
            }
        });
    }

    // #P0-audit 2026-08-30 : refonte complète du pourboire (mandat du fondateur - "à reprendre
    // en entier, pas à rafistoler"). L'ancien système DEVINAIT une direction ('before'/'after') et
    // EXTRAYAIT (divisait) le pourboire d'un montant "avec taxes" déjà saisi, même quand ce montant
    // ne contenait PAS encore de pourboire - ce qui faisait BAISSER le sous-total affiché dès qu'un
    // pourcentage était choisi (P0, reproduit : Québec, 114,98 $ avec taxes saisi SANS pourboire ->
    // "avant taxes" affiche correctement 100,00 $ -> clic sur "15 %" -> "avant taxes" TOMBE à
    // 86,96 $ sans que l'utilisateur ait changé son montant). Le nouveau système est TOUJOURS
    // additif : le pourboire s'ajoute au total, jamais ne s'en extrait, et ne touche JAMAIS les
    // champs "avant taxes"/"avec taxes"/taxes - il ne fait que lire leur valeur courante.
    var tipToggleBtn = document.getElementById('ct-tip-toggle-btn');
    var tipOptions = document.getElementById('ct-tip-options');
    var tipArrow = document.getElementById('ct-tip-toggle-arrow');
    var rtPctEl = document.getElementById('rt-tip-percent');
    var rtPresetBtns = document.querySelectorAll('.rt-tip-preset');
    var rtResult = document.getElementById('rt-result');
    var tipBaseRadios = document.querySelectorAll('input[name="ct-tip-base"]');
    var TIP_BASE_STORAGE_KEY = 'ct_tip_base_pref';
    var IS_AUTHENTICATED = {{ auth()->check() ? 'true' : 'false' }};

    function getProvinceRates() {
        var sel = document.getElementById('province');
        if (!sel || !sel.value) return null;
        return (window.taxConfig && window.taxConfig.tax_rates) ? window.taxConfig.tax_rates[sel.value] : null;
    }

    function isTipOpen() {
        return !!(tipToggleBtn && tipToggleBtn.getAttribute('aria-expanded') === 'true');
    }

    function getTipBase() {
        for (var i = 0; i < tipBaseRadios.length; i++) {
            if (tipBaseRadios[i].checked) return tipBaseRadios[i].value;
        }
        return 'before';
    }

    function round2(n) {
        // Même correctif Number.EPSILON que le moteur principal (calculator-simple.js _round) -
        // une seule règle d'arrondi dans tout l'outil.
        return Math.round((n + Number.EPSILON) * 100) / 100;
    }

    // Formate un POURCENTAGE (pas un montant) : virgule française, jamais de zéro inutile
    // (15 plutôt que 15,00 ; 12,5 plutôt que 12,50).
    function formatPercent(n) {
        var rounded = Math.round((n + Number.EPSILON) * 100) / 100;
        return rounded.toString().replace('.', ',');
    }

    function updateBaseDesc() {
        var desc = document.getElementById('rt-result-base-desc');
        if (desc) desc.textContent = (getTipBase() === 'after') ? '{{ __("du montant avec taxes") }}' : '{{ __("du montant avant taxes") }}';
    }

    // Calcul du pourboire - TOUJOURS additif, TOUJOURS relu depuis le DOM (aucun état interne
    // séparé à désynchroniser). base = sous-total (avant taxes) ou TTC (avec taxes) selon le choix
    // explicite de l'utilisateur (fieldset radio, défaut = avant taxes = usage québécois).
    function recalcTip() {
        var beforeEl = document.getElementById('amount-before-tax');
        var afterEl = document.getElementById('amount-after-tax');
        var subtotal = window.CalcParseAmount(beforeEl ? beforeEl.value : '');
        var total = window.CalcParseAmount(afterEl ? afterEl.value : '');
        var pct = window.CalcParseAmount(rtPctEl ? rtPctEl.value : '');
        var base = getTipBase();
        var baseAmount = (base === 'after') ? total : subtotal;
        var ready = isTipOpen() && !isNaN(pct) && pct >= 0 && !isNaN(total) && total > 0 && !isNaN(baseAmount) && baseAmount > 0;

        if (!ready) {
            if (rtResult) rtResult.style.display = 'none';
            if (window.simpleCalculator && window.simpleCalculator.state) {
                window.simpleCalculator.state.tipCalculation = null;
            }
            return;
        }

        var tipAmount = round2(baseAmount * pct / 100);
        var grandTotal = round2(total + tipAmount);

        updateBaseDesc();
        document.getElementById('rt-result-tip-pct').textContent = formatPercent(pct);
        document.getElementById('rt-result-tip-amount').textContent = window.CalcMoney(tipAmount);
        document.getElementById('rt-result-final-label').textContent = '{{ __("Total à payer (avec pourboire)") }}';
        document.getElementById('rt-result-final').textContent = window.CalcMoney(grandTotal);
        if (rtResult) rtResult.style.display = 'block';

        // Pont DRY vers "Diviser la facture" (calculator-simple.js._updateSplitCalculation lit déjà
        // state.tipCalculation.totalWithTip en priorité) : le pourboire actif se répercute donc
        // automatiquement dans le montant par personne, sans dupliquer la logique de répartition.
        if (window.simpleCalculator && window.simpleCalculator.state) {
            window.simpleCalculator.state.tipCalculation = { totalWithTip: grandTotal };
        }
    }

    // Préférence "base du pourboire" - réutilise le mécanisme existant des autres outils
    // (Modules/Tools/app/Http/Controllers/ToolPreferenceController.php, déjà utilisé par le
    // minuteur visuel et le constructeur de prompts) pour les visiteurs connectés, complété par
    // localStorage (déjà la convention de CE fichier pour tax_calc_history) pour tout le monde -
    // le défaut reste "avant taxes" tant qu'aucune préférence n'est trouvée.
    function applyTipBase(value) {
        var beforeRadio = document.getElementById('ct-tip-base-before');
        var afterRadio = document.getElementById('ct-tip-base-after');
        if (value === 'after' && afterRadio) afterRadio.checked = true;
        else if (beforeRadio) beforeRadio.checked = true;
        updateBaseDesc();
    }
    function saveTipBasePreference(value) {
        try { localStorage.setItem(TIP_BASE_STORAGE_KEY, value); } catch (e) { /* stockage indisponible - tant pis, silencieux */ }
        if (!IS_AUTHENTICATED) return;
        var csrfMeta = document.querySelector('meta[name="csrf-token"]');
        fetch('/api/tool-preferences/calculatrice-taxes', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfMeta ? csrfMeta.content : ''
            },
            body: JSON.stringify({ key: 'tip_base', value: value })
        }).catch(function () { /* anonyme ou hors-ligne : localStorage suffit déjà */ });
    }
    function loadTipBasePreference() {
        try {
            var local = localStorage.getItem(TIP_BASE_STORAGE_KEY);
            if (local === 'after' || local === 'before') applyTipBase(local);
        } catch (e) { /* silencieux */ }
        if (!IS_AUTHENTICATED) return;
        fetch('/api/tool-preferences/calculatrice-taxes', { headers: { 'Accept': 'application/json' } })
            .then(function (r) { return r.ok ? r.json() : null; })
            .then(function (data) {
                var v = data && data.preferences && data.preferences.tip_base;
                if (v === 'after' || v === 'before') applyTipBase(v);
            })
            .catch(function () { /* silencieux - localStorage a déjà fait de son mieux */ });
    }
    loadTipBasePreference();

    tipBaseRadios.forEach(function (radio) {
        radio.addEventListener('change', function () {
            if (!this.checked) return;
            saveTipBasePreference(this.value);
            recalcTip();
        });
    });

    // Toggle pourboire ouvrir/fermer
    if (tipToggleBtn) {
        tipToggleBtn.addEventListener('click', function() {
            var open = this.getAttribute('aria-expanded') === 'true';
            var newOpen = !open;
            this.setAttribute('aria-expanded', newOpen ? 'true' : 'false');
            if (tipOptions) tipOptions.style.display = newOpen ? 'block' : 'none';
            if (tipArrow) tipArrow.style.transform = newOpen ? 'rotate(180deg)' : 'rotate(0deg)';
            if (!newOpen) {
                // Fermeture : reset pourboire. Plus besoin de "réparer" les champs principaux -
                // le nouveau calcul ne les a jamais touchés.
                if (rtResult) rtResult.style.display = 'none';
                rtPresetBtns.forEach(function(b) { b.style.background = ''; b.style.color = ''; });
                if (rtPctEl) rtPctEl.value = '';
                if (window.simpleCalculator && window.simpleCalculator.state) {
                    window.simpleCalculator.state.tipCalculation = null;
                }
            } else {
                recalcTip();
            }
        });
    }

    // Tip% input → recalcul additif (jamais d'extraction)
    if (rtPctEl) {
        rtPctEl.addEventListener('input', function() {
            setTimeout(recalcTip, 0);
        });
    }

    // Tip preset clicks
    rtPresetBtns.forEach(function(btn) {
        btn.addEventListener('click', function() {
            var v = this.getAttribute('data-tip');
            if (rtPctEl) {
                rtPctEl.value = v;
                recalcTip();
            }
            rtPresetBtns.forEach(function(b) { b.style.background = ''; b.style.color = ''; });
            this.style.background = 'var(--c-primary, #064E5A)';
            this.style.color = '#fff';
        });
    });

    // Recalcul du pourboire quand les montants principaux changent (le moteur principal a déjà
    // mis à jour le champ miroir de façon synchrone à ce stade - cf. calculator-simple.js)
    var afterInputEl = document.getElementById('amount-after-tax');
    if (afterInputEl) {
        afterInputEl.addEventListener('input', function() {
            setTimeout(recalcTip, 0);
        });
    }
    var beforeInputEl = document.getElementById('amount-before-tax');
    if (beforeInputEl) {
        beforeInputEl.addEventListener('input', function() {
            setTimeout(recalcTip, 0);
        });
    }

    var provSel = document.getElementById('province');
    if (provSel) {
        provSel.addEventListener('change', function() {
            setTimeout(recalcTip, 0);
        });
    }

    // #15 + #16 v3 + #P0-audit : Init au load - lire ?p, ?a, ?s (source 'after' ou rien), ?t,
    // ?tb (base du pourboire, 'after' sinon 'before' par défaut). Rétrocompat ?m=reverse|reverse_tip
    (function initFromUrl() {
        try {
            var params = new URLSearchParams(window.location.search);
            var p = params.get('p');
            var a = params.get('a');
            var m = params.get('m');
            var s = params.get('s');
            var t = params.get('t');
            var tb = params.get('tb');
            if (p) {
                var sel = document.getElementById('province');
                if (sel) {
                    sel.value = p;
                    sel.dispatchEvent(new Event('change', {bubbles: true}));
                }
            }
            var useAfter = (s === 'after') || (m === 'reverse') || (m === 'reverse_tip');
            if (a) {
                var targetId = useAfter ? 'amount-after-tax' : 'amount-before-tax';
                var amountInput = document.getElementById(targetId);
                if (amountInput) {
                    amountInput.focus();
                    amountInput.value = a;
                    amountInput.dispatchEvent(new Event('input', {bubbles: true}));
                }
            }
            if (tb === 'after') applyTipBase('after');
            if (t) {
                if (tipToggleBtn) {
                    tipToggleBtn.setAttribute('aria-expanded', 'true');
                    if (tipOptions) tipOptions.style.display = 'block';
                    if (tipArrow) tipArrow.style.transform = 'rotate(180deg)';
                }
                if (rtPctEl) {
                    rtPctEl.value = t;
                    setTimeout(recalcTip, 50);
                }
            }
        } catch (e) { /* silent fail */ }
    })();

    // Historique localStorage
    var historyKey = 'tax_calc_history';
    var saveBtn = document.getElementById('save-history-btn');
    var histSection = document.getElementById('tax-history-section');
    var histList = document.getElementById('tax-history-list');
    var clearBtn = document.getElementById('clear-history-btn');

    function loadHistory() {
        var h = JSON.parse(localStorage.getItem(historyKey) || '[]');
        if (h.length === 0) { histSection.style.display = 'none'; return; }
        histSection.style.display = '';
        histList.innerHTML = h.map(function(item) {
            return '<div style="display:flex;justify-content:space-between;padding:4px 0;border-bottom:1px solid #f0f0f0;font-size:0.85rem;">' +
                '<span>' + item.province + ': ' + item.before + ' $ → ' + item.after + ' $</span>' +
                '<small style="color:#999;">' + item.date + '</small></div>';
        }).join('');
    }

    if (saveBtn) {
        saveBtn.style.display = '';
        saveBtn.addEventListener('click', function() {
            var h = JSON.parse(localStorage.getItem(historyKey) || '[]');
            var province = document.getElementById('province');
            var before = document.getElementById('amount-before-tax');
            var after = document.getElementById('amount-after-tax');
            if (!before || !before.value) return;
            h.unshift({
                province: province ? province.options[province.selectedIndex].text.split('(')[0].trim() : '',
                before: before.value,
                after: after ? after.value : '',
                date: new Date().toLocaleDateString('fr-CA')
            });
            if (h.length > 10) h = h.slice(0, 10);
            localStorage.setItem(historyKey, JSON.stringify(h));
            loadHistory();
            this.textContent = '✅ {{ __("Sauvegardé") }}';
            var self = this;
            setTimeout(function() { self.textContent = '💾 {{ __("Sauvegarder") }}'; }, 1500);
        });
    }

    if (clearBtn) {
        clearBtn.addEventListener('click', function() {
            localStorage.removeItem(historyKey);
            loadHistory();
        });
    }

    loadHistory();
});
</script>
@endpush
