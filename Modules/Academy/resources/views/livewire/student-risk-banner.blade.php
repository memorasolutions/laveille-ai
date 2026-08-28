<div>{{-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca --}}
@php
    $riskData   = $this->riskData;
    $showBanner = $riskData !== null && ($riskData['score'] ?? 0) >= 34;
    $score      = $showBanner ? (int) $riskData['score'] : 0;
    $isHigh     = $score >= 67;
    $reasons    = $showBanner ? ($riskData['reasons'] ?? []) : [];
@endphp

@if ($showBanner)
    @php
        if ($isHigh) {
            $bg      = 'var(--sys-status-warning-bg-subtle, #FFF7ED)';
            $border  = '#EA580C';
            $textCol = 'var(--sys-status-warning-text, #7C2D12)';
            $label   = 'Relance conseillée';
            $msg     = "Reprends où tu t'es arrêté – une leçon suffit pour relancer ton élan !";
        } else {
            $bg      = '#EEF8F9';
            $border  = '#064E5A';
            $textCol = '#064E5A';
            $label   = 'Conseil de la semaine';
            $msg     = '2 leçons cette semaine suffiront à te remettre pleinement en route !';
        }
    @endphp

    <div role="status"
         aria-label="{{ $label }}"
         style="background: {{ $bg }}; border-left: 4px solid {{ $border }}; border-radius: var(--sys-radius-md, 0.75rem); padding: 14px 18px; margin-bottom: 18px; font-family: var(--f-body, system-ui);">
        <div class="d-flex flex-wrap align-items-start gap-2">
            <span aria-hidden="true" style="font-size: 1.2rem; flex-shrink: 0;">
                {{ $isHigh ? '💡' : 'ℹ️' }}
            </span>
            <div style="flex: 1 1 0; min-width: 0;">
                {{-- Label textuel visible (WCAG AAA — pas couleur seule) --}}
                <p style="font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.05em; font-weight: 700; color: {{ $textCol }}; margin: 0 0 4px;">
                    {{ $label }}
                </p>
                <p style="margin: 0; font-weight: 600; color: {{ $textCol }}; font-size: 0.95rem;">
                    {{ $msg }}
                </p>
                @if (! empty($reasons))
                    <ul style="margin: 8px 0 0; padding-left: 1.2rem; font-size: 0.85rem; color: var(--sys-text-default, #1A1D23);">
                        @foreach ($reasons as $reason)
                            <li>{{ $reason }}</li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </div>
@endif
</div>
