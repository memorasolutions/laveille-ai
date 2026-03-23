{{--
    Composant onglets réutilisable — pill tabs 2026

    Usage :
    @include('fronttheme::partials.tabs', [
        'tabs' => [
            ['id' => 'tab1', 'label' => '🔐 Onglet 1'],
            ['id' => 'tab2', 'label' => '📝 Onglet 2'],
        ],
        'model' => 'tab',  // nom de la variable Alpine.js (default: 'tab')
    ])

    Le parent doit avoir x-data avec la variable $model initialisée au premier tab id.
--}}
@php $model = $model ?? 'tab'; @endphp
<div role="tablist" class="d-flex mb-4" style="background: var(--c-primary-light); border-radius: calc(var(--r-btn, 8px) + 2px); padding: 3px; gap: 3px;">
    @foreach($tabs as $tab)
        <button
            role="tab"
            type="button"
            @click="{{ $model }} = '{{ $tab['id'] }}'"
            @keydown.enter="{{ $model }} = '{{ $tab['id'] }}'"
            @keydown.space.prevent="{{ $model }} = '{{ $tab['id'] }}'"
            :aria-selected="{{ $model }} === '{{ $tab['id'] }}'"
            :tabindex="{{ $model }} === '{{ $tab['id'] }}' ? '0' : '-1'"
            class="btn flex-fill"
            :style="{{ $model }} === '{{ $tab['id'] }}'
                ? 'background: var(--c-primary); color: #fff; font-weight: 700; box-shadow: 0 2px 8px rgba(11,114,133,0.25);'
                : 'background: transparent; color: var(--c-dark); font-weight: 500;'"
            style="border: none; border-radius: var(--r-btn, 8px); font-family: var(--f-heading); font-size: 0.9rem; padding: 8px 16px; transition: all 0.25s ease;"
        >
            {{ $tab['label'] }}
        </button>
    @endforeach
</div>
