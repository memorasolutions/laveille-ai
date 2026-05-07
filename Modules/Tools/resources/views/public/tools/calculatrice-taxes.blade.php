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
    .calculator-app .split-section h3 { margin-top: 0; font-size: 1rem; }
    .calculator-app .card-body { padding: 1.5rem !important; }
    @media (max-width: 576px) {
        .calculator-app .tax-display-group { flex-direction: row !important; }
        .calculator-app .tax-display-group .form-group { min-width: 0; flex: 1; }
        .calculator-app .card-body { padding: 1rem !important; }
        .quick-amounts { justify-content: center; gap: 0.25rem !important; margin-bottom: 0.5rem !important; }
        .quick-amounts span { display: none !important; }
        .quick-amt-btn { padding: 3px 10px !important; font-size: 0.8rem !important; }
    }
</style>
@endpush

@section('content')
<section class="wpo-blog-single-section section-padding">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8 col-12">
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
                                    <p style="font-size: 0.85rem; color: var(--c-text-muted, #52586a); margin: 0 0 0.75rem 0; padding: 0.5rem 0.75rem; background: #f1f3f5; border-radius: 8px; border-left: 3px solid var(--c-primary, #064E5A);">
                                        💡 Saisissez le montant <strong>avant</strong> OU <strong>avec taxes</strong> — l'autre champ se calcule automatiquement.
                                    </p>

                                    {{-- Montants rapides --}}
                    <div class="quick-amounts" style="display: flex; gap: 0.5rem; flex-wrap: wrap; margin-bottom: 1rem;">
                        <span style="font-size: 0.85rem; color: var(--c-text-muted, #52586a); align-self: center;">{{ __('Montants rapides :') }}</span>
                        <button type="button" class="quick-amt-btn" data-amount="10" style="padding: 4px 12px; border: 1px solid #ddd; border-radius: 20px; background: #fff; cursor: pointer; font-size: 0.85rem;">10 $</button>
                        <button type="button" class="quick-amt-btn" data-amount="25" style="padding: 4px 12px; border: 1px solid #ddd; border-radius: 20px; background: #fff; cursor: pointer; font-size: 0.85rem;">25 $</button>
                        <button type="button" class="quick-amt-btn" data-amount="50" style="padding: 4px 12px; border: 1px solid #ddd; border-radius: 20px; background: #fff; cursor: pointer; font-size: 0.85rem;">50 $</button>
                        <button type="button" class="quick-amt-btn" data-amount="100" style="padding: 4px 12px; border: 1px solid #ddd; border-radius: 20px; background: #fff; cursor: pointer; font-size: 0.85rem;">100 $</button>
                        <button type="button" class="quick-amt-btn" data-amount="500" style="padding: 4px 12px; border: 1px solid #ddd; border-radius: 20px; background: #fff; cursor: pointer; font-size: 0.85rem;">500 $</button>
                    </div>

                    <div class="calculator-grid">
                                        <div class="form-group">
                                            <label for="amount-before-tax">
                                                {{ __('Montant avant taxes') }}
                                                <button type="button" class="ct-help-btn" data-help-key="avant_taxes" aria-label="Aide Montant avant taxes">ⓘ</button>
                                            </label>
                                            <div class="input-wrapper">
                                                <span class="currency-symbol">$</span>
                                                <input type="number" id="amount-before-tax" aria-label="Montant avant taxes" placeholder="0.00" step="0.01" min="0" inputmode="decimal" class="amount-input">
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
                                                    <input type="text" id="tax1-amount" readonly class="readonly-input" value="0.00">
                                                </div>
                                            </div>
                                            <div class="form-group" id="tax2-group">
                                                <label id="tax2-label">TVQ (9,975 %)</label>
                                                <div class="input-wrapper">
                                                    <span class="currency-symbol">$</span>
                                                    <input type="text" id="tax2-amount" readonly class="readonly-input" value="0.00">
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
                                                <input type="number" id="amount-after-tax" aria-label="Montant après taxes" placeholder="0.00" step="0.01" min="0" inputmode="decimal" class="amount-input total-amount">
                                            </div>
                                        </div>
                                    </div>

                                    {{-- #16 S84 v3 : Toggle pourboire toujours visible — actif si user saisit dans 'Montant avec taxes' --}}
                                    <div class="ct-tip-toggle-wrapper" id="ct-tip-toggle-wrapper" style="margin-bottom: 0.5rem;">
                                        <button type="button" id="ct-tip-toggle-btn" class="ct-btn ct-btn-outline" aria-expanded="false" aria-controls="ct-tip-options" style="width: 100%; text-align: left; padding: 0.6rem 0.9rem; display: flex; justify-content: space-between; align-items: center;">
                                            <span>🍽️ Le « Montant avec taxes » saisi inclut un pourboire ?</span>
                                            <span id="ct-tip-toggle-arrow" style="transition: transform 0.2s; font-size: 0.9rem;">▼</span>
                                        </button>
                                        <div id="ct-tip-options" style="display: none; padding: 0.9rem; background: #f8f9fa; border-radius: 8px; margin-top: 0.4rem; border: 1px solid #e2e6ea;">
                                            <div class="form-group" style="margin-bottom: 0;">
                                                <label for="rt-tip-percent" style="margin-bottom: 0.5rem;">{{ __('Pourcentage du pourboire') }}</label>
                                                <div style="display: flex; gap: 0.4rem; flex-wrap: wrap; align-items: center;">
                                                    <button type="button" class="rt-tip-preset ct-btn ct-btn-outline" data-tip="10" style="padding: 4px 12px; font-size: 0.85rem; min-width: 44px; min-height: 32px;">10 %</button>
                                                    <button type="button" class="rt-tip-preset ct-btn ct-btn-outline" data-tip="15" style="padding: 4px 12px; font-size: 0.85rem; min-width: 44px; min-height: 32px;">15 %</button>
                                                    <button type="button" class="rt-tip-preset ct-btn ct-btn-outline" data-tip="18" style="padding: 4px 12px; font-size: 0.85rem; min-width: 44px; min-height: 32px;">18 %</button>
                                                    <button type="button" class="rt-tip-preset ct-btn ct-btn-outline" data-tip="20" style="padding: 4px 12px; font-size: 0.85rem; min-width: 44px; min-height: 32px;">20 %</button>
                                                    <div class="input-wrapper" style="flex: 1; min-width: 100px; position: relative;">
                                                        <input type="number" id="rt-tip-percent" aria-label="Pourcentage personnalisé" placeholder="{{ __('Personnalisé') }}" step="0.5" min="0" max="100" inputmode="decimal" class="amount-input" style="padding-right: 2rem;">
                                                        <span style="position: absolute; right: 0.6rem; top: 50%; transform: translateY(-50%); color: var(--c-text-muted, #52586a);">%</span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div id="rt-result" style="display: none; margin-top: 1rem; padding-top: 0.75rem; border-top: 1px solid #e2e6ea; font-size: 0.9rem;">
                                                <div style="display: flex; justify-content: space-between; padding: 3px 0;"><span>{{ __('Pourboire') }} (<span id="rt-result-tip-pct">0</span>%)</span><strong id="rt-result-tip-amount">0.00 $</strong></div>
                                                <div style="display: flex; justify-content: space-between; padding: 3px 0;"><span>{{ __('Total avant pourboire (avec taxes)') }}</span><strong id="rt-result-after-tax">0.00 $</strong></div>
                                                <div style="display: flex; justify-content: space-between; padding: 3px 0;"><span id="rt-result-tax1-label">TPS</span><strong id="rt-result-tax1">0.00 $</strong></div>
                                                <div style="display: flex; justify-content: space-between; padding: 3px 0;"><span id="rt-result-tax2-label">TVQ</span><strong id="rt-result-tax2">0.00 $</strong></div>
                                                <div style="display: flex; justify-content: space-between; padding: 5px 0; border-top: 1px solid #e2e6ea; margin-top: 5px;"><span style="font-weight: 700;">{{ __('Sous-total avant taxes') }}</span><strong id="rt-result-subtotal" style="color: var(--c-primary, #064E5A); font-size: 1.05rem;">0.00 $</strong></div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="section-divider" id="tip-divider" style="display: none;"></div>

                                    <div class="tip-section" id="tip-section" style="display: none;">
                                        <div class="tip-popup-trigger">
                                            <button type="button" id="tip-popup-btn" class="ct-btn ct-btn-outline">{{ __('Ajouter un pourboire') }}</button>
                                        </div>
                                        <div class="tip-result" id="tip-display" style="display: none;">
                                            <div class="result-row">
                                                <span>{{ __('Pourboire') }} (<span id="tip-percentage">0</span>%)</span>
                                                <span class="tip-amount">$0.00</span>
                                            </div>
                                            <div class="result-row total-row">
                                                <span>{{ __('Total avec pourboire') }}</span>
                                                <span class="total-with-tip">$0.00</span>
                                            </div>
                                            <div class="tip-actions">
                                                <button type="button" id="tip-modify-btn" class="ct-btn ct-btn-outline">{{ __('Modifier') }}</button>
                                                <button type="button" id="tip-remove-btn" class="ct-btn ct-btn-outline">{{ __('Supprimer') }}</button>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="section-divider" id="split-divider" style="display: none;"></div>

                                    <div class="split-section" id="split-section" style="display: none;">
                                        <h3>{{ __('Diviser la facture') }}</h3>
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
                                                <span class="per-person-amount">$0.00</span>
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
@endsection

@push('scripts')
<script>
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

    // #16 S84 v2 : état mode courant (forward = HT→TTC, reverse = TTC→HT) + tipIncluded optionnel en mode reverse
    var currentMode = 'forward';
    var tipIncluded = false;

    // Helper DRY : extrait données calcul actuel du DOM (mode-aware #16 S84 v2)
    function getCalculationData() {
        var province = document.getElementById('province');
        var before = document.getElementById('amount-before-tax');
        var tax1 = document.getElementById('tax1-amount');
        var tax2 = document.getElementById('tax2-amount');
        var after = document.getElementById('amount-after-tax');
        var t1Label = document.getElementById('tax1-label');
        var t2Label = document.getElementById('tax2-label');
        var rtPct = document.getElementById('rt-tip-percent');
        var rtTipAmt = document.getElementById('rt-result-tip-amount');
        var rtAfter = document.getElementById('rt-result-after-tax');
        var rtSub = document.getElementById('rt-result-subtotal');
        var lines = [];
        if (province && province.value) lines.push('Province: ' + province.options[province.selectedIndex].text);

        if (currentMode === 'reverse') {
            if (tipIncluded && rtPct && rtPct.value) {
                if (after && after.value) lines.push('Total payé (incl. pourboire): ' + after.value + ' $');
                lines.push('Pourboire: ' + rtPct.value + ' % (' + (rtTipAmt ? rtTipAmt.textContent : '0') + ')');
                if (rtAfter) lines.push('Total avant pourboire (avec taxes): ' + rtAfter.textContent);
                if (t1Label && tax1) lines.push(t1Label.textContent + ': ' + tax1.value + ' $');
                if (t2Label && tax2 && tax2.value !== '0.00') lines.push(t2Label.textContent + ': ' + tax2.value + ' $');
                if (rtSub) lines.push('Sous-total avant taxes: ' + rtSub.textContent);
                return {
                    text: lines.join('\n'),
                    mode: 'reverse',
                    province: province ? province.value : '',
                    amount: after ? after.value : '',
                    tip: rtPct.value,
                    hasData: !!(province && province.value && after && after.value)
                };
            }
            // reverse simple (sans pourboire)
            if (after && after.value) lines.push('Avec taxes (saisi): ' + after.value + ' $');
            if (t1Label && tax1) lines.push(t1Label.textContent + ': ' + tax1.value + ' $');
            if (t2Label && tax2 && tax2.value !== '0.00') lines.push(t2Label.textContent + ': ' + tax2.value + ' $');
            if (before && before.value) lines.push('Avant taxes (calculé): ' + before.value + ' $');
            return {
                text: lines.join('\n'),
                mode: 'reverse',
                province: province ? province.value : '',
                amount: after ? after.value : '',
                hasData: !!(province && province.value && after && after.value)
            };
        }
        // forward (défaut)
        if (before && before.value) lines.push('Avant taxes: ' + before.value + ' $');
        if (t1Label && tax1) lines.push(t1Label.textContent + ': ' + tax1.value + ' $');
        if (t2Label && tax2 && tax2.value !== '0.00') lines.push(t2Label.textContent + ': ' + tax2.value + ' $');
        if (after && after.value) lines.push('Total: ' + after.value + ' $');
        return {
            text: lines.join('\n'),
            mode: 'forward',
            province: province ? province.value : '',
            amount: before ? before.value : '',
            hasData: !!(province && province.value && before && before.value)
        };
    }

    // Construire URL deep-link qui reconstruit le calcul (#15 + #16 S84 v2)
    function buildShareUrl(data) {
        var url = new URL(window.location.href);
        url.searchParams.delete('p');
        url.searchParams.delete('a');
        url.searchParams.delete('m');
        url.searchParams.delete('t');
        if (data.province) url.searchParams.set('p', data.province);
        if (data.amount) url.searchParams.set('a', data.amount);
        if (data.mode && data.mode !== 'forward') url.searchParams.set('m', data.mode);
        if (data.tip) url.searchParams.set('t', data.tip);
        return url.toString();
    }

    // Copier résultat
    var copyBtn = document.getElementById('copy-result-btn');
    if (copyBtn) {
        copyBtn.style.display = '';
        copyBtn.addEventListener('click', function() {
            var d = getCalculationData();
            navigator.clipboard.writeText(d.text);
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
                title: '{{ __("Estimation des taxes") }} — {{ config("app.name") }}',
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
                        self.textContent = '🔗 {{ __("Lien copié !") }}';
                        resetLabel();
                    });
            } else {
                // Fallback desktop : copy résumé + URL clipboard
                navigator.clipboard.writeText(d.text + '\n\n' + shareUrl);
                self.textContent = '🔗 {{ __("Lien copié !") }}';
                resetLabel();
            }
        });
    }

    // #16 S84 v2 : 2 onglets (forward / reverse) + toggle pourboire optionnel inline en reverse
    var modeBtns = document.querySelectorAll('.ct-mode-btn');
    var modeHint = document.getElementById('ct-mode-hint');
    var tipToggleWrapper = document.getElementById('ct-tip-toggle-wrapper');
    var tipToggleBtn = document.getElementById('ct-tip-toggle-btn');
    var tipOptions = document.getElementById('ct-tip-options');
    var tipArrow = document.getElementById('ct-tip-toggle-arrow');
    var rtPctEl = document.getElementById('rt-tip-percent');
    var rtPresetBtns = document.querySelectorAll('.rt-tip-preset');
    var rtResult = document.getElementById('rt-result');

    var modeHints = {
        'forward': '💡 {{ __("Calcul direct : saisissez le montant avant taxes pour voir TPS/TVQ et total.") }}',
        'reverse': '💡 {{ __("Calcul inversé : saisissez le montant avec taxes pour décomposer sous-total et taxes. Cochez « pourboire inclus » si applicable.") }}'
    };

    function switchMode(newMode) {
        currentMode = newMode;
        modeBtns.forEach(function(b) {
            var active = b.getAttribute('data-mode') === newMode;
            b.setAttribute('aria-selected', active ? 'true' : 'false');
            b.style.background = active ? 'var(--c-primary, #064E5A)' : 'transparent';
            b.style.color = active ? '#fff' : '#333';
        });
        if (modeHint) modeHint.innerHTML = modeHints[newMode];

        // Toggle pourboire visible uniquement en reverse
        if (tipToggleWrapper) tipToggleWrapper.style.display = (newMode === 'reverse') ? 'block' : 'none';

        // Si on quitte reverse, fermer le toggle pourboire et reset tipIncluded
        if (newMode !== 'reverse') {
            tipIncluded = false;
            if (tipToggleBtn) tipToggleBtn.setAttribute('aria-expanded', 'false');
            if (tipOptions) tipOptions.style.display = 'none';
            if (tipArrow) tipArrow.style.transform = 'rotate(0deg)';
            if (rtResult) rtResult.style.display = 'none';
        }

        if (newMode === 'reverse') {
            var afterEl = document.getElementById('amount-after-tax');
            if (afterEl) afterEl.focus();
        } else {
            var beforeEl = document.getElementById('amount-before-tax');
            if (beforeEl) beforeEl.focus();
        }
    }

    modeBtns.forEach(function(btn) {
        btn.addEventListener('click', function() {
            switchMode(this.getAttribute('data-mode'));
        });
    });

    function getProvinceRates() {
        var sel = document.getElementById('province');
        if (!sel || !sel.value) return null;
        return (window.taxConfig && window.taxConfig.tax_rates) ? window.taxConfig.tax_rates[sel.value] : null;
    }

    // Override : si tipIncluded actif en mode reverse, recompute reverse_tip et écrase l'engine
    function recalcReverseTipOverride() {
        if (currentMode !== 'reverse' || !tipIncluded) {
            if (rtResult) rtResult.style.display = 'none';
            return;
        }
        var rates = getProvinceRates();
        var afterEl = document.getElementById('amount-after-tax');
        var total = parseFloat((afterEl && afterEl.value || '').replace(',', '.'));
        var tipPct = parseFloat((rtPctEl && rtPctEl.value || '').replace(',', '.'));
        if (!rates || isNaN(total) || total <= 0 || isNaN(tipPct) || tipPct < 0) {
            if (rtResult) rtResult.style.display = 'none';
            return;
        }
        // total = montant payé avec pourboire (saisi dans amount-after-tax)
        var subtotalWithTax = total / (1 + tipPct / 100);
        var tipAmount = total - subtotalWithTax;
        var subtotal = subtotalWithTax / (1 + rates.total / 100);
        // Décomposition taxes
        var tax1Label = '', tax1Amount = 0, tax2Label = '', tax2Amount = 0;
        if (rates.hst) {
            tax1Label = 'TVH/HST (' + rates.hst + ' %)';
            tax1Amount = subtotal * rates.hst / 100;
        } else {
            if (rates.gst) {
                tax1Label = 'TPS/GST (' + rates.gst + ' %)';
                tax1Amount = subtotal * rates.gst / 100;
            }
            if (rates.qst) {
                tax2Label = 'TVQ/QST (' + rates.qst + ' %)';
                tax2Amount = subtotal * rates.qst / 100;
            } else if (rates.pst) {
                tax2Label = 'TVP/PST (' + rates.pst + ' %)';
                tax2Amount = subtotal * rates.pst / 100;
            }
        }
        var fmt = function(n) { return n.toFixed(2) + ' $'; };
        document.getElementById('rt-result-tip-pct').textContent = tipPct;
        document.getElementById('rt-result-tip-amount').textContent = fmt(tipAmount);
        document.getElementById('rt-result-after-tax').textContent = fmt(subtotalWithTax);
        document.getElementById('rt-result-tax1-label').textContent = tax1Label;
        document.getElementById('rt-result-tax1').textContent = fmt(tax1Amount);
        var t2Lab = document.getElementById('rt-result-tax2-label');
        var t2Amt = document.getElementById('rt-result-tax2');
        if (tax2Label) {
            t2Lab.textContent = tax2Label;
            t2Amt.textContent = fmt(tax2Amount);
            t2Lab.parentElement.style.display = 'flex';
        } else {
            t2Lab.parentElement.style.display = 'none';
        }
        document.getElementById('rt-result-subtotal').textContent = fmt(subtotal);
        if (rtResult) rtResult.style.display = 'block';

        // Override champs principaux : amount-before-tax = vrai sous-total HT (avant pourboire ET taxes)
        var beforeEl = document.getElementById('amount-before-tax');
        var t1Display = document.getElementById('tax1-amount');
        var t2Display = document.getElementById('tax2-amount');
        var t1LabelEl = document.getElementById('tax1-label');
        var t2LabelEl = document.getElementById('tax2-label');
        if (beforeEl) beforeEl.value = subtotal.toFixed(2);
        if (t1Display) t1Display.value = tax1Amount.toFixed(2);
        if (t2Display) t2Display.value = tax2Amount.toFixed(2);
        if (t1LabelEl && tax1Label) t1LabelEl.textContent = tax1Label;
        if (t2LabelEl && tax2Label) t2LabelEl.textContent = tax2Label;
    }

    // Toggle pourboire ouvrir/fermer
    if (tipToggleBtn) {
        tipToggleBtn.addEventListener('click', function() {
            var open = this.getAttribute('aria-expanded') === 'true';
            var newOpen = !open;
            this.setAttribute('aria-expanded', newOpen ? 'true' : 'false');
            if (tipOptions) tipOptions.style.display = newOpen ? 'block' : 'none';
            if (tipArrow) tipArrow.style.transform = newOpen ? 'rotate(180deg)' : 'rotate(0deg)';
            if (!newOpen) {
                // Fermeture : reset pourboire et redéclenche le calcul reverse simple
                tipIncluded = false;
                if (rtResult) rtResult.style.display = 'none';
                rtPresetBtns.forEach(function(b) { b.style.background = ''; b.style.color = ''; });
                if (rtPctEl) rtPctEl.value = '';
                var afterEl = document.getElementById('amount-after-tax');
                if (afterEl && afterEl.value) afterEl.dispatchEvent(new Event('input', {bubbles: true}));
            }
        });
    }

    // Tip% input → active tipIncluded + override calc
    if (rtPctEl) {
        rtPctEl.addEventListener('input', function() {
            tipIncluded = !!(this.value && parseFloat(this.value) > 0);
            recalcReverseTipOverride();
        });
    }

    // Tip preset clicks
    rtPresetBtns.forEach(function(btn) {
        btn.addEventListener('click', function() {
            var v = this.getAttribute('data-tip');
            if (rtPctEl) {
                rtPctEl.value = v;
                tipIncluded = true;
                recalcReverseTipOverride();
            }
            rtPresetBtns.forEach(function(b) { b.style.background = ''; b.style.color = ''; });
            this.style.background = 'var(--c-primary, #064E5A)';
            this.style.color = '#fff';
        });
    });

    // Quand engine reverse simple recompute, override si tipIncluded actif
    var afterInputEl = document.getElementById('amount-after-tax');
    if (afterInputEl) {
        afterInputEl.addEventListener('input', function() {
            if (tipIncluded && currentMode === 'reverse') {
                setTimeout(recalcReverseTipOverride, 0);
            }
        });
    }

    var provSel = document.getElementById('province');
    if (provSel) {
        provSel.addEventListener('change', function() {
            if (currentMode === 'reverse' && tipIncluded) setTimeout(recalcReverseTipOverride, 0);
        });
    }

    // #15 + #16 S84 v2 : Init au load — lire ?p, ?a, ?m, ?t (rétrocompat ancien m=reverse_tip)
    (function initFromUrl() {
        try {
            var params = new URLSearchParams(window.location.search);
            var p = params.get('p');
            var a = params.get('a');
            var m = params.get('m');
            var t = params.get('t');
            if (p) {
                var sel = document.getElementById('province');
                if (sel) {
                    sel.value = p;
                    sel.dispatchEvent(new Event('change', {bubbles: true}));
                }
            }
            // m=reverse_tip = ancien lien : bascule reverse + active toggle pourboire
            var modeToSet = (m === 'reverse_tip') ? 'reverse' : (m === 'reverse' ? 'reverse' : 'forward');
            if (modeToSet !== 'forward') switchMode(modeToSet);
            if (a) {
                var targetId = (modeToSet === 'reverse') ? 'amount-after-tax' : 'amount-before-tax';
                var amountInput = document.getElementById(targetId);
                if (amountInput) {
                    amountInput.value = a;
                    amountInput.dispatchEvent(new Event('input', {bubbles: true}));
                }
            }
            if (t && modeToSet === 'reverse') {
                // Ouvrir le toggle pourboire et remplir tip%
                if (tipToggleBtn) {
                    tipToggleBtn.setAttribute('aria-expanded', 'true');
                    if (tipOptions) tipOptions.style.display = 'block';
                    if (tipArrow) tipArrow.style.transform = 'rotate(180deg)';
                }
                if (rtPctEl) {
                    rtPctEl.value = t;
                    tipIncluded = true;
                    setTimeout(recalcReverseTipOverride, 50);
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
