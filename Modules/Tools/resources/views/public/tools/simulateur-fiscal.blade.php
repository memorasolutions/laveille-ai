<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends(fronttheme_layout())
@section('title', $tool->name . ' - ' . config('app.name'))
@section('breadcrumb')
    @include('fronttheme::partials.breadcrumb', ['breadcrumbTitle' => $tool->name, 'breadcrumbItems' => [__('Outils'), $tool->name]])
@endsection
@push('styles')
<style>.page-wrapper { overflow: visible !important; }</style>
@endpush
@push('styles')
<script>
function fiscalSim(cfg) {
    function calcTax(taxableIncome, personalAmount, brackets) {
        var t = Math.max(0, taxableIncome - personalAmount);
        var tax = 0;
        for (var i = 0; i < brackets.length; i++) {
            var b = brackets[i];
            var width = (b.max !== null ? b.max : Infinity) - b.min;
            if (t <= 0) break;
            var inBracket = Math.min(t, width);
            tax += inBracket * b.rate;
            t -= inBracket;
        }
        return tax;
    }
    function getMarginal(taxableIncome, personalAmount, brackets) {
        var t = Math.max(0, taxableIncome - personalAmount);
        for (var i = 0; i < brackets.length; i++) {
            var b = brackets[i];
            var width = (b.max !== null ? b.max : Infinity) - b.min;
            if (t <= width) return b.rate;
            t -= width;
        }
        return brackets[brackets.length - 1].rate;
    }
    return {
        cfg: cfg,
        income: cfg.ui.defaultIncome,
        overtime: 0,
        isEmployee: true,
        rrsp: 0,
        includeRRQ: true,
        includeAE: true,
        includeRQAP: true,
        showHelp: {},
        hoveredQc: null,
        hoveredFed: null,
        showDisclaimer: true,
        fmt: function(n) { return Math.round(n).toLocaleString('fr-CA') + ' $'; },
        get totalIncome() { return this.income + this.overtime; },
        get maxRrsp() { return Math.min(Math.round(this.totalIncome * cfg.rrsp.maxRate), cfg.rrsp.maxAmount); },
        get taxableIncome() { return Math.max(0, this.totalIncome - this.rrsp); },
        get fedTaxBeforeAbatement() { return calcTax(this.taxableIncome, cfg.federal.personalAmount, cfg.federal.brackets); },
        get fedTax() { return this.fedTaxBeforeAbatement * (1 - cfg.federal.abatementQC); },
        get abatementSaving() { return this.fedTaxBeforeAbatement * cfg.federal.abatementQC; },
        get provTax() { return calcTax(this.taxableIncome, cfg.provincial.personalAmount, cfg.provincial.brackets); },
        get qpp() {
            if (!this.includeRRQ) return 0;
            var base = Math.max(0, Math.min(this.totalIncome, cfg.qpp.maxPensionableEarnings) - cfg.qpp.basicExemption);
            var rate = this.isEmployee ? cfg.qpp.employeeRate : cfg.qpp.selfEmployedRate;
            var max = this.isEmployee ? cfg.qpp.maxEmployeeContribution : cfg.qpp.maxSelfEmployedContribution;
            return Math.min(base * rate, max);
        },
        get ei() { return (this.isEmployee && this.includeAE) ? Math.min(Math.min(this.totalIncome, cfg.ei.maxInsurableEarnings) * cfg.ei.rateQC, cfg.ei.maxPremiumQC) : 0; },
        get qpip() {
            if (!this.includeRQAP) return 0;
            var ins = Math.min(this.totalIncome, cfg.qpip.maxInsurableEarnings);
            return this.isEmployee ? Math.min(ins * cfg.qpip.employeeRate, cfg.qpip.maxEmployeePremium) : Math.min(ins * cfg.qpip.selfEmployedRate, cfg.qpip.maxSelfEmployedPremium);
        },
        get fss() { return this.isEmployee ? 0 : Math.max(0, this.totalIncome - cfg.fss.threshold) * cfg.fss.rate; },
        get totalDed() { return Math.max(0, this.fedTax) + Math.max(0, this.provTax) + this.qpp + this.ei + this.qpip + this.fss; },
        get net() { return this.totalIncome - this.totalDed; },
        get rate() { return this.totalIncome > 0 ? (this.totalDed / this.totalIncome * 100).toFixed(1) : '0.0'; },
        get monthly() { return this.net / 12; },
        get biweekly() { return this.net / 26; },
        get weekly() { return this.net / 52; },
        get netPct() { return this.totalIncome > 0 ? Math.round(this.net / this.totalIncome * 100) : 100; },
        get fedMarginal() { return Math.round(getMarginal(this.taxableIncome, cfg.federal.personalAmount, cfg.federal.brackets) * (1 - cfg.federal.abatementQC) * 1000) / 10; },
        get provMarginal() { return Math.round(getMarginal(this.taxableIncome, cfg.provincial.personalAmount, cfg.provincial.brackets) * 1000) / 10; },
        get combinedMarginal() { return Math.round((this.fedMarginal + this.provMarginal) * 10) / 10; },
        get overtimeTax() { return this.overtime > 0 ? this.overtime * this.combinedMarginal / 100 : 0; },
        get overtimeNet() { return this.overtime - this.overtimeTax; },
        get overtimeKeepPct() { return this.overtime > 0 ? Math.round((1 - this.combinedMarginal / 100) * 100) : 100; },
        // Helpers pour les paliers dans le template
        provBracketAmount: function(i) {
            var b = cfg.provincial.brackets;
            var pa = cfg.provincial.personalAmount;
            var cumStart = pa;
            for (var j = 0; j < i; j++) cumStart += (b[j].max !== null ? b[j].max : Infinity) - b[j].min;
            var width = (b[i].max !== null ? b[i].max : Infinity) - b[i].min;
            return Math.min(Math.max(this.taxableIncome - cumStart, 0), width);
        },
        fedBracketAmount: function(i) {
            var b = cfg.federal.brackets;
            var pa = cfg.federal.personalAmount;
            var cumStart = pa;
            for (var j = 0; j < i; j++) cumStart += (b[j].max !== null ? b[j].max : Infinity) - b[j].min;
            var width = (b[i].max !== null ? b[i].max : Infinity) - b[i].min;
            return Math.min(Math.max(this.taxableIncome - cumStart, 0), width);
        },
        provBracketStart: function(i) {
            var pa = cfg.provincial.personalAmount;
            var start = pa;
            for (var j = 0; j < i; j++) start += (cfg.provincial.brackets[j].max !== null ? cfg.provincial.brackets[j].max : Infinity) - cfg.provincial.brackets[j].min;
            return start;
        },
        fedBracketStart: function(i) {
            var pa = cfg.federal.personalAmount;
            var start = pa;
            for (var j = 0; j < i; j++) start += (cfg.federal.brackets[j].max !== null ? cfg.federal.brackets[j].max : Infinity) - cfg.federal.brackets[j].min;
            return start;
        }
    };
}
</script>
@endpush
@section('content')
<section class="wpo-blog-single-section section-padding">
    <div class="container">
        <script>window.__fiscalConfig = @json($toolConfig);</script>
        <div class="row justify-content-center tool-fullscreen-target" style="display: flex; flex-wrap: wrap;" x-data="fiscalSim(window.__fiscalConfig)">
                    {{-- Colonne gauche sticky (contrôles) --}}
                    <div class="col-lg-4 col-12 mb-3">
                        <div style="position: sticky; top: 80px;">
                            <div class="card shadow-sm" style="border-radius: var(--r-base);">
                                <div class="card-body p-3">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <h2 style="font-family: var(--f-heading); font-weight: 800; color: var(--c-dark); font-size: 1.1rem; margin: 0;">{{ __('Paramètres') }}</h2>
                                        <div class="d-flex gap-1">
                                            @include('tools::partials.fullscreen-btn')
                                            <button class="btn btn-sm" @click="jQuery('#fiscalHelpModal').modal('show')" style="background: var(--c-primary); color: #fff; border-radius: 50%; width: 28px; height: 28px; font-weight: 700; font-size: 0.8rem; padding: 0; line-height: 28px; flex-shrink: 0;">?</button>
                                        </div>
                                    </div>

                                    {{-- Salarié/autonome --}}
                                    <div class="d-flex gap-1 mb-3">
                                        <button class="btn btn-sm flex-fill" :style="isEmployee ? 'background: var(--c-primary); color: #fff;' : ''" @click="isEmployee = true" style="border-radius: var(--r-btn); font-weight: 600; font-size: 0.8rem;">{{ __('Salarié') }}</button>
                                        <button class="btn btn-sm flex-fill" :style="!isEmployee ? 'background: var(--c-accent); color: #fff;' : ''" @click="isEmployee = false" style="border-radius: var(--r-btn); font-weight: 600; font-size: 0.8rem;">{{ __('Autonome') }}</button>
                                    </div>

                                    {{-- Revenu --}}
                                    <div class="mb-2">
                                        <label class="form-label fw-medium mb-0" style="font-size: 0.8rem;">{{ __('Revenu brut') }}</label>
                                        <div class="input-group input-group-sm">
                                            <input type="number" class="form-control" x-model.number="income" step="1000" aria-label="Revenu annuel brut" min="0" max="500000">
                                            <span class="input-group-text">$</span>
                                        </div>
                                        <input type="range" class="form-range" x-model.number="income" min="0" max="300000" step="1000" style="margin-top: 2px;">
                                    </div>

                                    {{-- REER --}}
                                    <div class="mb-2">
                                        <label class="form-label fw-medium mb-0" style="font-size: 0.8rem; display: flex; align-items: center; gap: 4px;">{{ __('REER') }} <small class="text-muted" x-text="'(max ' + fmt(maxRrsp) + ')'"></small> <button type="button" @click="jQuery('#reerHelpModal').modal('show')" style="background: var(--c-primary); color: #fff; border-radius: 50%; width: 18px; height: 18px; font-weight: 700; font-size: 0.6rem; padding: 0; line-height: 18px; border: none; cursor: pointer; flex-shrink: 0;">?</button></label>
                                        <div class="input-group input-group-sm">
                                            <input type="number" class="form-control" x-model.number="rrsp" aria-label="Cotisation REER" :max="maxRrsp" min="0" step="500">
                                            <span class="input-group-text">$</span>
                                        </div>
                                        <input type="range" class="form-range" x-model.number="rrsp" min="0" :max="maxRrsp" step="500" style="margin-top: 2px;">
                                    </div>

                                    {{-- Temps supp --}}
                                    <div class="mb-2">
                                        <label class="form-label fw-medium mb-0" style="font-size: 0.8rem;">{{ __('Temps supp.') }}</label>
                                        <div class="input-group input-group-sm">
                                            <input type="number" class="form-control" x-model.number="overtime" step="1000" min="0" max="100000" aria-label="{{ __('Temps supplémentaire') }}">
                                            <span class="input-group-text">$</span>
                                        </div>
                                        <input type="range" class="form-range" x-model.number="overtime" min="0" max="50000" step="500" style="margin-top: 2px;">
                                    </div>

                                    {{-- Cotisations --}}
                                    <div class="mb-2" style="font-size: 0.75rem;">
                                        <label style="display: inline-flex; align-items: center; gap: 4px; cursor: pointer;">
                                            <input type="checkbox" x-model="includeRRQ" style="display:inline-block !important; width:14px; height:14px; accent-color: var(--c-primary); margin: 0;">
                                            <strong>RRQ</strong>
                                        </label>
                                        <label style="display: inline-flex; align-items: center; gap: 4px; cursor: pointer; margin-left: 8px;" x-show="isEmployee">
                                            <input type="checkbox" x-model="includeAE" style="display:inline-block !important; width:14px; height:14px; accent-color: var(--c-primary); margin: 0;">
                                            <strong>AE</strong>
                                        </label>
                                        <label style="display: inline-flex; align-items: center; gap: 4px; cursor: pointer; margin-left: 8px;">
                                            <input type="checkbox" x-model="includeRQAP" style="display:inline-block !important; width:14px; height:14px; accent-color: var(--c-primary); margin: 0;">
                                            <strong>RQAP</strong>
                                        </label>
                                    </div>

                                    {{-- Résumé rapide --}}
                                    <div class="p-2 rounded text-center" style="background: #D1FAE5; border: 2px solid #059669;">
                                        <small class="text-muted d-block" style="font-size: 0.7rem;">{{ __('Revenu net') }}</small>
                                        <strong style="color: #059669; font-size: 1.3rem;" x-text="fmt(net)"></strong>
                                        <div style="font-size: 0.65rem; color: #6b7280;">
                                            <span x-text="fmt(monthly) + '/{{ __('mois') }}'"></span> · <span x-text="rate + ' %'"></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Colonne droite (résultats) --}}
                    <div class="col-lg-8 col-12">
                      {{-- Avertissement fermable --}}
                      {{-- Avertissement fermable (showDisclaimer dans le x-data parent) --}}
                      <div x-show="showDisclaimer" x-transition style="background: #FEF3C7; border: 1px solid #F59E0B; border-radius: 8px; padding: 12px 16px; margin-bottom: 12px; font-size: 0.8rem; position: relative;">
                          <button @click="showDisclaimer = false" style="position: absolute; top: 8px; right: 12px; background: none; border: none; color: #92400e; font-size: 1.1rem; cursor: pointer; line-height: 1; padding: 0;" aria-label="Fermer l'avertissement">&times;</button>
                          <strong style="color: #92400e;" x-text="cfg.texts.disclaimer.title"></strong>
                          <p class="mb-0 mt-1" style="font-size: 0.75rem; color: #78350f;" x-text="cfg.texts.disclaimer.body.replace('{year}', cfg.meta.year)"></p>
                      </div>
                      <button x-show="!showDisclaimer" @click="showDisclaimer = true" x-transition style="background: none; border: none; color: #92400e; font-size: 0.7rem; cursor: pointer; margin-bottom: 8px; padding: 0; text-decoration: underline;" x-text="cfg.texts.disclaimer.showButton"></button>

                      <div class="card shadow-sm" style="border-radius: var(--r-base);">
                        <div class="card-body p-4">
                        <h1 style="font-family: var(--f-heading); font-weight: 800; color: var(--c-dark); font-size: 1.3rem; margin-bottom: 1rem;">{{ __('Résultats') }}</h1>

                        {{-- Taux marginal --}}
                        <div class="d-flex justify-content-center gap-3 mb-3 p-2 rounded" style="background: #f8f9fa;">
                            <div class="text-center">
                                <small class="text-muted d-block">{{ __('Taux marginal') }}</small>
                                <strong style="font-size: 1.2rem; color: var(--c-dark);" x-text="combinedMarginal.toFixed(1) + ' %'"></strong>
                            </div>
                            <div class="text-center" style="border-left: 1px solid #dee2e6; padding-left: 1rem;">
                                <small class="text-muted d-block">{{ __('Fédéral') }}</small>
                                <span style="font-weight: 600;" x-text="fedMarginal + ' %'"></span>
                            </div>
                            <div class="text-center" style="border-left: 1px solid #dee2e6; padding-left: 1rem;">
                                <small class="text-muted d-block">{{ __('Provincial') }}</small>
                                <span style="font-weight: 600;" x-text="provMarginal + ' %'"></span>
                            </div>
                        </div>

                        {{-- Barre visuelle --}}
                        <div class="d-flex justify-content-between mb-1" style="font-size: 0.8rem;">
                            <span style="color: #059669; font-weight: 600;" x-text="'{{ __('Net') }} ' + netPct + ' %'"></span>
                            <span style="color: #DC2626; font-weight: 600;" x-text="'{{ __('Déductions') }} ' + (100 - netPct) + ' %'"></span>
                        </div>
                        <div class="mb-4" style="background: #fecaca; border-radius: 8px; height: 16px; overflow: hidden; display: flex;">
                            <div style="background: #059669; height: 100%; transition: width 0.3s; border-radius: 8px 0 0 8px;" :style="'width:' + netPct + '%'"></div>
                        </div>

                        {{-- Dashboard --}}
                        <div class="row text-center mb-4">
                            <div class="col-6 col-md-4 mb-3">
                                <div class="p-3 rounded" style="background: var(--c-primary-light); border-left: 4px solid var(--c-primary);">
                                    <small class="text-muted d-block">{{ __('Impôt fédéral') }} <small style="color: #059669;">({{ __('abatt. QC') }})</small></small>
                                    <strong style="color: var(--c-primary); font-size: 1.1rem;" x-text="fmt(Math.max(0, fedTax))"></strong>
                                </div>
                            </div>
                            <div class="col-6 col-md-4 mb-3">
                                <div class="p-3 rounded" style="background: var(--c-primary-light); border-left: 4px solid var(--c-primary);">
                                    <small class="text-muted d-block">{{ __('Impôt provincial') }}</small>
                                    <strong style="color: var(--c-primary); font-size: 1.1rem;" x-text="fmt(Math.max(0, provTax))"></strong>
                                </div>
                            </div>
                            <div class="col-6 col-md-4 mb-3">
                                <div class="p-3 rounded" style="background: #FEF3C7; border-left: 4px solid #F59E0B;">
                                    <small class="text-muted d-block" x-text="isEmployee ? 'RRQ (QPP)' : 'RRQ (QPP) x2'"></small>
                                    <strong style="color: #D97706; font-size: 1.1rem;" x-text="fmt(qpp)"></strong>
                                </div>
                            </div>
                            <div class="col-6 col-md-4 mb-3" x-show="isEmployee">
                                <div class="p-3 rounded" style="background: #FEF3C7; border-left: 4px solid #F59E0B;">
                                    <small class="text-muted d-block">{{ __('AE (assurance-emploi)') }}</small>
                                    <strong style="color: #D97706; font-size: 1.1rem;" x-text="fmt(ei)"></strong>
                                </div>
                            </div>
                            <div class="col-6 col-md-4 mb-3">
                                <div class="p-3 rounded" style="background: #FEF3C7; border-left: 4px solid #F59E0B;">
                                    <small class="text-muted d-block" x-text="isEmployee ? 'RQAP' : 'RQAP (autonome)'"></small>
                                    <strong style="color: #D97706; font-size: 1.1rem;" x-text="fmt(qpip)"></strong>
                                </div>
                            </div>
                            <div class="col-6 col-md-4 mb-3" x-show="!isEmployee">
                                <div class="p-3 rounded" style="background: #FEF3C7; border-left: 4px solid #F59E0B;">
                                    <small class="text-muted d-block">{{ __('FSS (fonds santé)') }}</small>
                                    <strong style="color: #D97706; font-size: 1.1rem;" x-text="fmt(fss)"></strong>
                                </div>
                            </div>
                            <div class="col-6 col-md-4 mb-3">
                                <div class="p-3 rounded" style="background: #FEE2E2; border-left: 4px solid #DC2626;">
                                    <small class="text-muted d-block">{{ __('Total déductions') }}</small>
                                    <strong style="color: #DC2626; font-size: 1.1rem;" x-text="fmt(totalDed)"></strong>
                                </div>
                            </div>
                        </div>

                        {{-- Détails revenus --}}
                        <div class="row mb-4">
                            <div class="col-6 col-md-3 mb-2">
                                <div class="p-2 rounded text-center" style="background: #D1FAE5;">
                                    <small class="text-muted d-block" style="font-size: 0.7rem;">{{ __('Par mois') }}</small>
                                    <strong style="color: #059669;" x-text="fmt(monthly)"></strong>
                                </div>
                            </div>
                            <div class="col-6 col-md-3 mb-2">
                                <div class="p-2 rounded text-center" style="background: #D1FAE5;">
                                    <small class="text-muted d-block" style="font-size: 0.7rem;">{{ __('Aux 2 semaines') }}</small>
                                    <strong style="color: #059669;" x-text="fmt(biweekly)"></strong>
                                </div>
                            </div>
                            <div class="col-6 col-md-3 mb-2">
                                <div class="p-2 rounded text-center" style="background: #D1FAE5;">
                                    <small class="text-muted d-block" style="font-size: 0.7rem;">{{ __('Par semaine') }}</small>
                                    <strong style="color: #059669;" x-text="fmt(weekly)"></strong>
                                </div>
                            </div>
                            <div class="col-6 col-md-3 mb-2">
                                <div class="p-2 rounded text-center" style="background: var(--c-primary-light);">
                                    <small class="text-muted d-block" style="font-size: 0.7rem;">{{ __('Taux effectif') }}</small>
                                    <strong style="color: var(--c-dark);" x-text="rate + ' %'"></strong>
                                </div>
                            </div>
                        </div>

                        {{-- Note autonome --}}
                        <div class="p-3 rounded mb-3" x-show="!isEmployee" style="background: #FEF3C7; border: 1px solid #F59E0B; font-size: 0.85rem;">
                            <strong style="color: #D97706;">{{ __('Note — travailleur autonome') }}</strong>
                            <ul class="mb-0 mt-1" style="padding-left: 1.2rem;">
                                <li>{{ __('Le RRQ est doublé (part employeur + employé). La moitié est déductible.') }}</li>
                                <li>{{ __('Pas d\'assurance-emploi (AE), mais le RQAP au taux autonome (0,878 %).') }}</li>
                                <li>{{ __('Le FSS (fonds des services de sante) s\'applique sur le revenu net d\'entreprise.') }}</li>
                                <li>{{ __('Les dépenses d\'entreprise (bureau, véhicule, équipement) ne sont pas incluses ici.') }}</li>
                            </ul>
                        </div>

                        {{-- Visualisation des paliers (barres + tableau) --}}
                        <details class="mb-4" open>
                            <summary style="cursor: pointer; font-family: var(--f-heading); font-weight: 600; color: var(--c-dark);">{{ __('Visualisation des paliers d\'imposition') }}</summary>
                            <div class="mt-3 p-3 rounded" style="background: #f8f9fa;">
                                <p class="text-muted small mb-3">{{ __('Survolez la barre pour voir les détails de chaque palier.') }}</p>

                                {{-- Québec --}}
                                <div class="mb-4">
                                    <div class="d-flex justify-content-between mb-1" style="font-size: 0.75rem;">
                                        <strong style="color: var(--c-dark);">{{ __('Québec') }}</strong>
                                        <span style="color: var(--c-dark);" x-text="fmt(Math.max(0, provTax))"></span>
                                    </div>
                                    <div style="position: relative;">
                                        {{-- Barre --}}
                                        <div style="display: flex; height: 24px; border-radius: 6px; overflow: hidden; background: #e2e8f0;">
                                            <div :style="'width:' + Math.min(100, 18056 / Math.max(taxableIncome, 1) * 100) + '%; background: #d1fae5; border-right: 2px dashed #059669;'" style="transition: width 0.3s; cursor: pointer;" @mouseenter="hoveredQc = 0" @mouseleave="hoveredQc = null"></div>
                                            <div :style="'width:' + Math.max(0, Math.min(taxableIncome - 18056, 53255) / Math.max(taxableIncome, 1) * 100) + '%; background: #86efac;'" style="transition: width 0.3s; cursor: pointer;" @mouseenter="hoveredQc = 1" @mouseleave="hoveredQc = null"></div>
                                            <div :style="'width:' + Math.max(0, Math.min(Math.max(taxableIncome - 71311, 0), 53240) / Math.max(taxableIncome, 1) * 100) + '%; background: #fbbf24;'" style="transition: width 0.3s; cursor: pointer;" @mouseenter="hoveredQc = 2" @mouseleave="hoveredQc = null"></div>
                                            <div :style="'width:' + Math.max(0, Math.min(Math.max(taxableIncome - 124551, 0), 23095) / Math.max(taxableIncome, 1) * 100) + '%; background: #fb923c;'" style="transition: width 0.3s; cursor: pointer;" @mouseenter="hoveredQc = 3" @mouseleave="hoveredQc = null"></div>
                                            <div :style="'width:' + Math.max(0, Math.max(taxableIncome - 147646, 0) / Math.max(taxableIncome, 1) * 100) + '%; background: #ef4444;'" style="transition: width 0.3s; cursor: pointer;" @mouseenter="hoveredQc = 4" @mouseleave="hoveredQc = null"></div>
                                        </div>
                                        {{-- Tooltip (hors overflow) --}}
                                        <div x-show="hoveredQc !== null" x-transition.opacity style="position: absolute; bottom: 32px; left: 50%; transform: translateX(-50%); background: #fff; padding: 10px 14px; border-radius: 8px; box-shadow: 0 4px 16px rgba(0,0,0,0.15); z-index: 100; font-size: 0.8rem; white-space: nowrap; pointer-events: none;">
                                            <template x-if="hoveredQc === 0"><div><strong>0 %</strong> — {{ __('Non imposé') }}<br>0 $ à 18 056 $<br>{{ __('Montant') }} : <span x-text="fmt(Math.min(taxableIncome, 18056))"></span><br>{{ __('Impôt') }} : 0 $</div></template>
                                            <template x-if="hoveredQc === 1"><div><strong>14 %</strong><br>18 056 $ à 71 311 $<br>{{ __('Montant') }} : <span x-text="fmt(Math.min(Math.max(taxableIncome - 18056, 0), 53255))"></span><br>{{ __('Impôt') }} : <span x-text="fmt(Math.min(Math.max(taxableIncome - 18056, 0), 53255) * 0.14)"></span></div></template>
                                            <template x-if="hoveredQc === 2"><div><strong>19 %</strong><br>71 311 $ à 124 551 $<br>{{ __('Montant') }} : <span x-text="fmt(Math.min(Math.max(taxableIncome - 71311, 0), 53240))"></span><br>{{ __('Impôt') }} : <span x-text="fmt(Math.min(Math.max(taxableIncome - 71311, 0), 53240) * 0.19)"></span></div></template>
                                            <template x-if="hoveredQc === 3"><div><strong>24 %</strong><br>124 551 $ à 147 646 $<br>{{ __('Montant') }} : <span x-text="fmt(Math.min(Math.max(taxableIncome - 124551, 0), 23095))"></span><br>{{ __('Impôt') }} : <span x-text="fmt(Math.min(Math.max(taxableIncome - 124551, 0), 23095) * 0.24)"></span></div></template>
                                            <template x-if="hoveredQc === 4"><div><strong>25,75 %</strong><br>147 646 $ et +<br>{{ __('Montant') }} : <span x-text="fmt(Math.max(taxableIncome - 147646, 0))"></span><br>{{ __('Impôt') }} : <span x-text="fmt(Math.max(taxableIncome - 147646, 0) * 0.2575)"></span></div></template>
                                        </div>
                                    </div>
                                    {{-- Tableau Québec --}}
                                    <table style="width: 100%; margin-top: 8px; font-size: 0.75rem; border-collapse: collapse;">
                                        <thead><tr style="border-bottom: 1px solid #dee2e6; color: #6b7280;">
                                            <th style="width: 20px; padding: 4px;"></th>
                                            <th style="padding: 4px; text-align: left;">{{ __('Taux') }}</th>
                                            <th style="padding: 4px; text-align: left;">{{ __('Tranche') }}</th>
                                            <th style="padding: 4px; text-align: right;">{{ __('Imposable') }}</th>
                                            <th style="padding: 4px; text-align: right;">{{ __('Impôt') }}</th>
                                        </tr></thead>
                                        <tbody>
                                            <tr style="background: #fff;" :style="hoveredQc === 0 ? 'background: #d1fae5;' : ''">
                                                <td style="padding: 4px;"><span style="display: inline-block; width: 12px; height: 12px; background: #d1fae5; border: 1px dashed #059669; border-radius: 3px;"></span></td>
                                                <td style="padding: 4px;">0 %</td>
                                                <td style="padding: 4px;">0 $ à 18 056 $</td>
                                                <td style="padding: 4px; text-align: right;" x-text="fmt(Math.min(taxableIncome, 18056))"></td>
                                                <td style="padding: 4px; text-align: right;">0 $</td>
                                            </tr>
                                            <tr x-show="taxableIncome > 18056" style="background: #f9fafb;" :style="hoveredQc === 1 ? 'background: #dcfce7;' : ''">
                                                <td style="padding: 4px;"><span style="display: inline-block; width: 12px; height: 12px; background: #86efac; border-radius: 3px;"></span></td>
                                                <td style="padding: 4px;">14 %</td>
                                                <td style="padding: 4px;">18 056 $ à 71 311 $</td>
                                                <td style="padding: 4px; text-align: right;" x-text="fmt(Math.min(Math.max(taxableIncome - 18056, 0), 53255))"></td>
                                                <td style="padding: 4px; text-align: right;" x-text="fmt(Math.min(Math.max(taxableIncome - 18056, 0), 53255) * 0.14)"></td>
                                            </tr>
                                            <tr x-show="taxableIncome > 71311" style="background: #fff;" :style="hoveredQc === 2 ? 'background: #fef9c3;' : ''">
                                                <td style="padding: 4px;"><span style="display: inline-block; width: 12px; height: 12px; background: #fbbf24; border-radius: 3px;"></span></td>
                                                <td style="padding: 4px;">19 %</td>
                                                <td style="padding: 4px;">71 311 $ à 124 551 $</td>
                                                <td style="padding: 4px; text-align: right;" x-text="fmt(Math.min(Math.max(taxableIncome - 71311, 0), 53240))"></td>
                                                <td style="padding: 4px; text-align: right;" x-text="fmt(Math.min(Math.max(taxableIncome - 71311, 0), 53240) * 0.19)"></td>
                                            </tr>
                                            <tr x-show="taxableIncome > 124551" style="background: #f9fafb;" :style="hoveredQc === 3 ? 'background: #ffedd5;' : ''">
                                                <td style="padding: 4px;"><span style="display: inline-block; width: 12px; height: 12px; background: #fb923c; border-radius: 3px;"></span></td>
                                                <td style="padding: 4px;">24 %</td>
                                                <td style="padding: 4px;">124 551 $ à 147 646 $</td>
                                                <td style="padding: 4px; text-align: right;" x-text="fmt(Math.min(Math.max(taxableIncome - 124551, 0), 23095))"></td>
                                                <td style="padding: 4px; text-align: right;" x-text="fmt(Math.min(Math.max(taxableIncome - 124551, 0), 23095) * 0.24)"></td>
                                            </tr>
                                            <tr x-show="taxableIncome > 147646" style="background: #fff;" :style="hoveredQc === 4 ? 'background: #fee2e2;' : ''">
                                                <td style="padding: 4px;"><span style="display: inline-block; width: 12px; height: 12px; background: #ef4444; border-radius: 3px;"></span></td>
                                                <td style="padding: 4px;">25,75 %</td>
                                                <td style="padding: 4px;">147 646 $ et +</td>
                                                <td style="padding: 4px; text-align: right;" x-text="fmt(Math.max(taxableIncome - 147646, 0))"></td>
                                                <td style="padding: 4px; text-align: right;" x-text="fmt(Math.max(taxableIncome - 147646, 0) * 0.2575)"></td>
                                            </tr>
                                            <tr style="border-top: 2px solid #374151; font-weight: 700;">
                                                <td colspan="3" style="padding: 4px; text-align: right;">{{ __('Total provincial') }}</td>
                                                <td style="padding: 4px; text-align: right;" x-text="fmt(taxableIncome)"></td>
                                                <td style="padding: 4px; text-align: right;" x-text="fmt(Math.max(0, provTax))"></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>

                                {{-- Fédéral --}}
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between mb-1" style="font-size: 0.75rem;">
                                        <strong style="color: var(--c-dark);">{{ __('Fédéral (après abattement 16,5 %)') }}</strong>
                                        <span style="color: var(--c-dark);" x-text="fmt(Math.max(0, fedTax))"></span>
                                    </div>
                                    <div style="position: relative;">
                                        {{-- Barre --}}
                                        <div style="display: flex; height: 24px; border-radius: 6px; overflow: hidden; background: #e2e8f0;">
                                            <div :style="'width:' + Math.min(100, 16129 / Math.max(taxableIncome, 1) * 100) + '%; background: #dbeafe; border-right: 2px dashed #3b82f6;'" style="transition: width 0.3s; cursor: pointer;" @mouseenter="hoveredFed = 0" @mouseleave="hoveredFed = null"></div>
                                            <div :style="'width:' + Math.max(0, Math.min(taxableIncome - 16129, 57375) / Math.max(taxableIncome, 1) * 100) + '%; background: #bfdbfe;'" style="transition: width 0.3s; cursor: pointer;" @mouseenter="hoveredFed = 1" @mouseleave="hoveredFed = null"></div>
                                            <div :style="'width:' + Math.max(0, Math.min(Math.max(taxableIncome - 73504, 0), 57375) / Math.max(taxableIncome, 1) * 100) + '%; background: #93c5fd;'" style="transition: width 0.3s; cursor: pointer;" @mouseenter="hoveredFed = 2" @mouseleave="hoveredFed = null"></div>
                                            <div :style="'width:' + Math.max(0, Math.min(Math.max(taxableIncome - 130879, 0), 63132) / Math.max(taxableIncome, 1) * 100) + '%; background: #60a5fa;'" style="transition: width 0.3s; cursor: pointer;" @mouseenter="hoveredFed = 3" @mouseleave="hoveredFed = null"></div>
                                            <div :style="'width:' + Math.max(0, Math.max(taxableIncome - 194011, 0) / Math.max(taxableIncome, 1) * 100) + '%; background: #3b82f6;'" style="transition: width 0.3s; cursor: pointer;" @mouseenter="hoveredFed = 4" @mouseleave="hoveredFed = null"></div>
                                        </div>
                                        {{-- Tooltip --}}
                                        <div x-show="hoveredFed !== null" x-transition.opacity style="position: absolute; bottom: 32px; left: 50%; transform: translateX(-50%); background: #fff; padding: 10px 14px; border-radius: 8px; box-shadow: 0 4px 16px rgba(0,0,0,0.15); z-index: 100; font-size: 0.8rem; white-space: nowrap; pointer-events: none;">
                                            <template x-if="hoveredFed === 0"><div><strong>0 %</strong> — {{ __('Non imposé') }}<br>0 $ à 16 129 $<br>{{ __('Montant') }} : <span x-text="fmt(Math.min(taxableIncome, 16129))"></span><br>{{ __('Impôt') }} : 0 $</div></template>
                                            <template x-if="hoveredFed === 1"><div><strong>12,1 %</strong><br>16 129 $ à 73 504 $<br>{{ __('Montant') }} : <span x-text="fmt(Math.min(Math.max(taxableIncome - 16129, 0), 57375))"></span><br>{{ __('Impôt') }} : <span x-text="fmt(Math.min(Math.max(taxableIncome - 16129, 0), 57375) * 0.121)"></span></div></template>
                                            <template x-if="hoveredFed === 2"><div><strong>17,1 %</strong><br>73 504 $ à 130 879 $<br>{{ __('Montant') }} : <span x-text="fmt(Math.min(Math.max(taxableIncome - 73504, 0), 57375))"></span><br>{{ __('Impôt') }} : <span x-text="fmt(Math.min(Math.max(taxableIncome - 73504, 0), 57375) * 0.171)"></span></div></template>
                                            <template x-if="hoveredFed === 3"><div><strong>21,7 %</strong><br>130 879 $ à 194 011 $<br>{{ __('Montant') }} : <span x-text="fmt(Math.min(Math.max(taxableIncome - 130879, 0), 63132))"></span><br>{{ __('Impôt') }} : <span x-text="fmt(Math.min(Math.max(taxableIncome - 130879, 0), 63132) * 0.217)"></span></div></template>
                                            <template x-if="hoveredFed === 4"><div><strong>24,2 %</strong><br>194 011 $ et +<br>{{ __('Montant') }} : <span x-text="fmt(Math.max(taxableIncome - 194011, 0))"></span><br>{{ __('Impôt') }} : <span x-text="fmt(Math.max(taxableIncome - 194011, 0) * 0.242)"></span></div></template>
                                        </div>
                                    </div>
                                    {{-- Tableau Fédéral --}}
                                    <table style="width: 100%; margin-top: 8px; font-size: 0.75rem; border-collapse: collapse;">
                                        <thead><tr style="border-bottom: 1px solid #dee2e6; color: #6b7280;">
                                            <th style="width: 20px; padding: 4px;"></th>
                                            <th style="padding: 4px; text-align: left;">{{ __('Taux') }}</th>
                                            <th style="padding: 4px; text-align: left;">{{ __('Tranche') }}</th>
                                            <th style="padding: 4px; text-align: right;">{{ __('Imposable') }}</th>
                                            <th style="padding: 4px; text-align: right;">{{ __('Impôt') }}</th>
                                        </tr></thead>
                                        <tbody>
                                            <tr style="background: #fff;" :style="hoveredFed === 0 ? 'background: #dbeafe;' : ''">
                                                <td style="padding: 4px;"><span style="display: inline-block; width: 12px; height: 12px; background: #dbeafe; border: 1px dashed #3b82f6; border-radius: 3px;"></span></td>
                                                <td style="padding: 4px;">0 %</td>
                                                <td style="padding: 4px;">0 $ à 16 129 $</td>
                                                <td style="padding: 4px; text-align: right;" x-text="fmt(Math.min(taxableIncome, 16129))"></td>
                                                <td style="padding: 4px; text-align: right;">0 $</td>
                                            </tr>
                                            <tr x-show="taxableIncome > 16129" style="background: #f9fafb;" :style="hoveredFed === 1 ? 'background: #dbeafe;' : ''">
                                                <td style="padding: 4px;"><span style="display: inline-block; width: 12px; height: 12px; background: #bfdbfe; border-radius: 3px;"></span></td>
                                                <td style="padding: 4px;">12,1 %</td>
                                                <td style="padding: 4px;">16 129 $ à 73 504 $</td>
                                                <td style="padding: 4px; text-align: right;" x-text="fmt(Math.min(Math.max(taxableIncome - 16129, 0), 57375))"></td>
                                                <td style="padding: 4px; text-align: right;" x-text="fmt(Math.min(Math.max(taxableIncome - 16129, 0), 57375) * 0.121)"></td>
                                            </tr>
                                            <tr x-show="taxableIncome > 73504" style="background: #fff;" :style="hoveredFed === 2 ? 'background: #dbeafe;' : ''">
                                                <td style="padding: 4px;"><span style="display: inline-block; width: 12px; height: 12px; background: #93c5fd; border-radius: 3px;"></span></td>
                                                <td style="padding: 4px;">17,1 %</td>
                                                <td style="padding: 4px;">73 504 $ à 130 879 $</td>
                                                <td style="padding: 4px; text-align: right;" x-text="fmt(Math.min(Math.max(taxableIncome - 73504, 0), 57375))"></td>
                                                <td style="padding: 4px; text-align: right;" x-text="fmt(Math.min(Math.max(taxableIncome - 73504, 0), 57375) * 0.171)"></td>
                                            </tr>
                                            <tr x-show="taxableIncome > 130879" style="background: #f9fafb;" :style="hoveredFed === 3 ? 'background: #dbeafe;' : ''">
                                                <td style="padding: 4px;"><span style="display: inline-block; width: 12px; height: 12px; background: #60a5fa; border-radius: 3px;"></span></td>
                                                <td style="padding: 4px;">21,7 %</td>
                                                <td style="padding: 4px;">130 879 $ à 194 011 $</td>
                                                <td style="padding: 4px; text-align: right;" x-text="fmt(Math.min(Math.max(taxableIncome - 130879, 0), 63132))"></td>
                                                <td style="padding: 4px; text-align: right;" x-text="fmt(Math.min(Math.max(taxableIncome - 130879, 0), 63132) * 0.217)"></td>
                                            </tr>
                                            <tr x-show="taxableIncome > 194011" style="background: #fff;" :style="hoveredFed === 4 ? 'background: #dbeafe;' : ''">
                                                <td style="padding: 4px;"><span style="display: inline-block; width: 12px; height: 12px; background: #3b82f6; border-radius: 3px;"></span></td>
                                                <td style="padding: 4px;">24,2 %</td>
                                                <td style="padding: 4px;">194 011 $ et +</td>
                                                <td style="padding: 4px; text-align: right;" x-text="fmt(Math.max(taxableIncome - 194011, 0))"></td>
                                                <td style="padding: 4px; text-align: right;" x-text="fmt(Math.max(taxableIncome - 194011, 0) * 0.242)"></td>
                                            </tr>
                                            <tr style="border-top: 2px solid #374151; font-weight: 700;">
                                                <td colspan="3" style="padding: 4px; text-align: right;">{{ __('Total fédéral') }}</td>
                                                <td style="padding: 4px; text-align: right;" x-text="fmt(taxableIncome)"></td>
                                                <td style="padding: 4px; text-align: right;" x-text="fmt(Math.max(0, fedTax))"></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </details>

                        {{-- Concepts importants --}}
                        <details class="mb-4">
                            <summary style="cursor: pointer; font-family: var(--f-heading); font-weight: 600; color: var(--c-dark);">{{ __('Concepts importants à retenir') }}</summary>
                            <div class="mt-3 row">
                                <div class="col-md-6 mb-3">
                                    <div class="p-3 rounded h-100" style="background: #f0fdf4; border-left: 3px solid #059669;">
                                        <strong style="color: #059669;">{{ __('Système progressif') }}</strong>
                                        <p class="mb-0" style="font-size: 0.8rem;">{{ __('Plus vous gagnez, plus le taux augmente, mais seulement sur la partie qui dépasse chaque palier. Vous ne perdez jamais d\'argent en gagnant plus.') }}</p>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="p-3 rounded h-100" style="background: #eff6ff; border-left: 3px solid #3b82f6;">
                                        <strong style="color: #3b82f6;">{{ __('Taux effectif vs marginal') }}</strong>
                                        <p class="mb-0" style="font-size: 0.8rem;">{{ __('Le taux effectif est votre impôt total divisé par votre revenu. Le taux marginal est le taux sur votre prochain dollar gagné.') }}</p>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="p-3 rounded h-100" style="background: #fef3c7; border-left: 3px solid #f59e0b;">
                                        <strong style="color: #d97706;">{{ __('Mythe du temps supplémentaire') }}</strong>
                                        <p class="mb-0" style="font-size: 0.8rem;">{{ __('Vous gardez toujours au moins 47 % de chaque dollar additionnel, même au taux le plus élevé. Votre salaire de base n\'est jamais affecté par le temps supp.') }}</p>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="p-3 rounded h-100" style="background: #faf5ff; border-left: 3px solid #8b5cf6;">
                                        <strong style="color: #7c3aed;">{{ __('Abattement du Québec') }}</strong>
                                        <p class="mb-0" style="font-size: 0.8rem;">{{ __('Les résidents du Québec bénéficient d\'une réduction de 16,5 % sur l\'impôt fédéral car le Québec administre ses propres programmes sociaux.') }}</p>
                                    </div>
                                </div>
                            </div>
                        </details>

                        <p class="text-muted mb-0" style="font-size: 0.75rem;">{{ __('Estimation 2025 (Québec). Inclut l\'abattement fédéral du Québec (16,5 %), RRQ, AE/RQAP, FSS et montants personnels de base. Ne tient pas compte des crédits d\'impôt additionnels ou situations particulières.') }}</p>
                        </div>
                      </div>
                    </div>
                </div>
    </div>
</section>

{{-- Modale aide --}}
<div class="modal fade" id="fiscalHelpModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content" style="border-radius: var(--r-base);">
            <div class="modal-header" style="background: var(--c-primary); border-radius: var(--r-base) var(--r-base) 0 0;">
                <h4 class="modal-title" style="color: #fff; font-family: var(--f-heading); font-weight: 700;">{{ __('Comment utiliser ce simulateur') }}</h4>
                <button type="button" onclick="jQuery('#fiscalHelpModal').modal('hide')" style="background: none; border: none; color: #fff !important; opacity: 1; font-size: 1.5rem; font-weight: 700; cursor: pointer; float: right;">&times;</button>
            </div>
            <div class="modal-body" style="padding: 2rem;">
                <h4 style="font-family: var(--f-heading); font-weight: 700; color: var(--c-dark); border-bottom: 2px solid var(--c-primary); padding-bottom: 0.5rem;">{{ __('Les paramètres') }}</h4>
                <ul>
                    <li><strong>{{ __('Revenu brut') }}</strong> — {{ __('votre salaire annuel avant impôts et déductions') }}</li>
                    <li><strong>{{ __('REER') }}</strong> — {{ __('cotisation au régime enregistré d\'épargne-retraite, réduit votre revenu imposable') }}</li>
                    <li><strong>{{ __('Temps supplémentaire') }}</strong> — {{ __('revenus additionnels imposés au taux marginal (pas au taux moyen)') }}</li>
                    <li><strong>{{ __('Salarié vs autonome') }}</strong> — {{ __('un autonome paie le double du RRQ et n\'a pas d\'assurance-emploi') }}</li>
                </ul>

                <h4 style="font-family: var(--f-heading); font-weight: 700; color: var(--c-dark); border-bottom: 2px solid var(--c-primary); padding-bottom: 0.5rem; margin-top: 1.5rem;">{{ __('Les cotisations') }}</h4>
                <ul>
                    <li><strong>RRQ</strong> — {{ __('régime de rentes du Québec, pour votre retraite (6,4 % salarié, 12,8 % autonome)') }}</li>
                    <li><strong>AE</strong> — {{ __('assurance-emploi, en cas de perte d\'emploi (1,27 %, salariés seulement)') }}</li>
                    <li><strong>RQAP</strong> — {{ __('assurance parentale, pour les congés parentaux (0,494 % salarié, 0,878 % autonome)') }}</li>
                </ul>

                <h4 style="font-family: var(--f-heading); font-weight: 700; color: var(--c-dark); border-bottom: 2px solid var(--c-primary); padding-bottom: 0.5rem; margin-top: 1.5rem;">{{ __('L\'abattement du Québec') }}</h4>
                <p>{{ __('Le Québec administre ses propres programmes sociaux. En échange, l\'impôt fédéral est réduit de 16,5 % pour tous les résidents du Québec. C\'est automatiquement appliqué dans ce simulateur.') }}</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn" onclick="jQuery('#fiscalHelpModal').modal('hide')" style="background: var(--c-primary); color: #fff; border-radius: var(--r-btn);">{{ __('Compris !') }}</button>
            </div>
        </div>
    </div>
</div>

{{-- Modale aide REER --}}
<div class="modal fade" id="reerHelpModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content" style="border-radius: var(--r-base);">
            <div class="modal-header" style="background: var(--c-primary); border-radius: var(--r-base) var(--r-base) 0 0;">
                <h4 class="modal-title" style="color: #fff; font-family: var(--f-heading); font-weight: 700;">{{ __('Comprendre le REER') }}</h4>
                <button type="button" onclick="jQuery('#reerHelpModal').modal('hide')" style="background: none; border: none; color: #fff !important; opacity: 1; font-size: 1.5rem; font-weight: 700; cursor: pointer; float: right;">&times;</button>
            </div>
            <div class="modal-body" style="padding: 24px;">
                <div class="mb-3 p-3 rounded" style="background: #f0fdf4; border-left: 3px solid #059669;">
                    <strong style="color: #059669;">{{ __('Qu\'est-ce que le REER ?') }}</strong>
                    <p class="mb-0 mt-1" style="font-size: 0.85rem;">{{ __('Le régime enregistré d\'épargne-retraite (REER) est un compte d\'épargne avec avantages fiscaux. Les cotisations réduisent votre revenu imposable, ce qui diminue votre impôt à payer pour l\'année courante.') }}</p>
                </div>

                <div class="mb-3 p-3 rounded" style="background: #eff6ff; border-left: 3px solid #3b82f6;">
                    <strong style="color: #3b82f6;">{{ __('Plafond de cotisation 2025') }}</strong>
                    <p class="mb-1 mt-1" style="font-size: 0.85rem;">{{ __('Le maximum est le moindre de :') }}</p>
                    <ul style="font-size: 0.85rem; margin-bottom: 0; padding-left: 20px;">
                        <li>{{ __('18 % de votre revenu gagné de l\'année précédente') }}</li>
                        <li>{{ __('32 490 $ (plafond annuel fixé par l\'ARC pour 2025)') }}</li>
                    </ul>
                    <p class="mb-0 mt-2" style="font-size: 0.8rem; color: #6b7280;">{{ __('Les droits de cotisation inutilisés des années précédentes s\'accumulent et s\'ajoutent à votre plafond. Ce simulateur utilise 18 % du revenu entré comme estimation simplifiée.') }}</p>
                </div>

                <div class="mb-3 p-3 rounded" style="background: #fef3c7; border-left: 3px solid #f59e0b;">
                    <strong style="color: #d97706;">{{ __('Impact sur vos impôts') }}</strong>
                    <p class="mb-0 mt-1" style="font-size: 0.85rem;">{{ __('Chaque dollar cotisé au REER réduit votre revenu imposable. L\'économie dépend de votre taux marginal d\'imposition. Par exemple, avec un taux marginal combiné de 37 %, une cotisation de 10 000 $ vous fait économiser environ 3 700 $ d\'impôt.') }}</p>
                </div>

                <div class="p-3 rounded" style="background: #faf5ff; border-left: 3px solid #8b5cf6;">
                    <strong style="color: #7c3aed;">{{ __('Points importants') }}</strong>
                    <ul style="font-size: 0.85rem; margin-bottom: 0; margin-top: 8px; padding-left: 20px;">
                        <li>{{ __('Les retraits du REER sont imposables (sauf RAP et REEP)') }}</li>
                        <li>{{ __('La date limite de cotisation est habituellement le 1er mars de l\'année suivante') }}</li>
                        <li>{{ __('Le CELI est une alternative sans déduction, mais sans imposition au retrait') }}</li>
                        <li>{{ __('Consultez votre avis de cotisation de l\'ARC pour connaître votre plafond exact') }}</li>
                    </ul>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn" onclick="jQuery('#reerHelpModal').modal('hide')" style="background: var(--c-primary); color: #fff; border-radius: var(--r-btn);">{{ __('Compris !') }}</button>
            </div>
        </div>
    </div>
</div>
@endsection
