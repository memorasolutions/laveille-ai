{{-- Modules/Core/resources/views/components/education-pricing-card.blade.php
     Encadré standard "Offre spéciale éducation" réutilisable (DRY).
     Usage : @include('core::components.education-pricing-card', ['tool' => $tool])
     Condition silencieuse : affiche uniquement si $tool->has_education_pricing
     Requires : has_education_pricing, education_pricing_type, education_pricing_details, education_pricing_url sur le model --}}

@if(!empty($tool) && $tool->has_education_pricing)
<div style="background: linear-gradient(135deg, #f0fdf4 0%, #ecfdf5 50%, #f0fdfa 100%); border: 1px solid #d1fae5; border-radius: 6px; padding: 24px; margin-bottom: 24px;">
    <div style="display: flex; align-items: flex-start; gap: 16px;">
        <span style="font-size: 32px; line-height: 1;" aria-hidden="true">🎓</span>
        <div style="flex: 1;">
            <h4 style="margin: 0 0 12px 0; font-size: 18px; font-weight: 700; color: #1a1a1a;">
                {{ __('Offre spéciale pour l\'éducation') }}
                @if($tool->education_pricing_type === 'free')
                    <span style="display:inline-block;background:#065f46;color:#fff;font-size:11px;padding:2px 10px;border-radius:4px;font-weight:600;margin-left:8px;vertical-align:middle;">{{ __('Gratuit') }}</span>
                @elseif($tool->education_pricing_type === 'discount')
                    <span style="display:inline-block;background:#ea580c;color:#fff;font-size:11px;padding:2px 10px;border-radius:4px;font-weight:600;margin-left:8px;vertical-align:middle;">{{ __('Rabais') }}</span>
                @elseif($tool->education_pricing_type === 'trial')
                    <span style="display:inline-block;background:var(--c-primary);color:#fff;font-size:11px;padding:2px 10px;border-radius:4px;font-weight:600;margin-left:8px;vertical-align:middle;">{{ __('Essai') }}</span>
                @endif
            </h4>
            @if($tool->education_pricing_details)
                <p style="margin: 0 0 16px 0; font-size: 14px; line-height: 1.6; color: var(--c-text-muted);">{{ $tool->education_pricing_details }}</p>
            @endif
            @if($tool->education_pricing_url)
                <a href="{{ $tool->education_pricing_url }}" target="_blank" rel="noopener noreferrer" class="ct-btn ct-btn-sm" style="background:#065f46;color:#fff;border-color:#065f46;">
                    {{ __('En savoir plus') }} →
                </a>
            @endif
        </div>
    </div>
</div>
@endif
