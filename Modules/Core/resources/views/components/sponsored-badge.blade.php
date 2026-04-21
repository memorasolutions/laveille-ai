@props(['tool', 'size' => 'md'])

@if($tool->isSponsored())
    @php
        $sizeStyle = match($size) {
            'sm' => 'font-size: 10px; padding: 2px 8px;',
            'lg' => 'font-size: 14px; padding: 6px 14px;',
            default => 'font-size: 12px; padding: 4px 10px;',
        };
    @endphp
    <span style="display: inline-flex; align-items: center; gap: 4px; border-radius: 14px; border: 1px solid #FCD34D; background-color: #FEF3C7; color: #92400E; font-weight: 600; {{ $sizeStyle }}" title="Publicité — contenu sponsorisé" role="note">★ Sponsorisé</span>
@endif
