@extends('auth::layouts.user-frontend')

@section('title', __('Mes sauvegardes') . ' - ' . config('app.name'))

@section('user-content')

<h2 style="font-family: var(--f-heading, inherit); font-weight: 700; margin: 0 0 5px;">
    💾 {{ __('Mes sauvegardes') }}
</h2>
<p style="color: var(--c-text-muted); margin: 0 0 20px;">{{ __('Vos configurations d\'outils sauvegardées.') }}</p>

<div x-data="{ filter: 'all' }">

    {{-- Chips filtres --}}
    @if($items->count() > 0)
    <div style="display: flex !important; flex-wrap: wrap !important; gap: 8px; margin-bottom: 20px;">
        <button @click="filter = 'all'" class="btn btn-sm"
                :style="filter === 'all'
                    ? 'background: var(--c-primary); color: #fff; border: none; border-radius: 20px; padding: 6px 16px; font-size: 13px; font-weight: 600;'
                    : 'background: #fff; color: var(--c-text-muted); border: 1px solid #e5e7eb; border-radius: 20px; padding: 6px 16px; font-size: 13px; font-weight: 600;'">
            {{ __('Tous') }} <span style="opacity: 0.7; margin-left: 4px;">{{ $items->count() }}</span>
        </button>
        @php
            $typeLabels = [
                'prompt' => ['icon' => '✨', 'label' => __('Prompts'), 'color' => '#8B5CF6'],
                'team' => ['icon' => '👥', 'label' => __('Générateur d\'équipes'), 'color' => '#0B7285'],
                'draw' => ['icon' => '🎲', 'label' => __('Tirage de présentations'), 'color' => '#E67E22'],
            ];
        @endphp
        @foreach($types as $type)
            @php $meta = $typeLabels[$type] ?? ['icon' => '📄', 'label' => ucfirst($type), 'color' => '#6B7280']; @endphp
            <button @click="filter = '{{ $type }}'" class="btn btn-sm"
                    :style="filter === '{{ $type }}'
                        ? 'background: {{ $meta['color'] }}; color: #fff; border: none; border-radius: 20px; padding: 6px 16px; font-size: 13px; font-weight: 600;'
                        : 'background: #fff; color: var(--c-text-muted); border: 1px solid #e5e7eb; border-radius: 20px; padding: 6px 16px; font-size: 13px; font-weight: 600;'">
                {{ $meta['icon'] }} {{ $meta['label'] }}
                <span style="opacity: 0.7; margin-left: 4px;">{{ $items->where('type', $type)->count() }}</span>
            </button>
        @endforeach
    </div>
    @endif

    {{-- Liste unifiée --}}
    @if($items->isEmpty())
        <div style="text-align: center; padding: 60px 20px; color: var(--c-text-muted);">
            <div style="font-size: 2.5rem; margin-bottom: 12px;">💾</div>
            <h3 style="font-family: var(--f-heading); margin-bottom: 8px;">{{ __('Aucune sauvegarde') }}</h3>
            <p>{{ __('Utilisez les outils du site et sauvegardez vos configurations pour les retrouver ici.') }}</p>
            @if(Route::has('tools.index'))
                <a href="{{ route('tools.index') }}" style="display: inline-block; background: var(--c-primary); color: #fff; padding: 10px 24px; border-radius: 8px; font-weight: 600; text-decoration: none; margin-top: 12px;">{{ __('Explorer les outils') }}</a>
            @endif
        </div>
    @else
        <div style="display: flex !important; flex-direction: column !important; gap: 10px;">
            @foreach($items as $item)
            <div x-show="filter === 'all' || filter === '{{ $item->type }}'" x-transition
                 style="background: #fff; border: 1px solid #E5E7EB; border-radius: 10px; padding: 14px 18px;">
                <div style="display: flex !important; align-items: center !important; gap: 12px;">
                    {{-- Icône outil --}}
                    <div style="width: 40px; height: 40px; border-radius: 10px; background: {{ $item->tool_color }}15; display: flex !important; align-items: center !important; justify-content: center !important; flex-shrink: 0; font-size: 18px;">
                        {{ $item->tool_icon }}
                    </div>
                    {{-- Contenu --}}
                    <div style="flex: 1 !important; min-width: 0; overflow: hidden;">
                        <div style="display: flex !important; align-items: center !important; gap: 8px; margin-bottom: 2px;">
                            <strong style="font-size: 14px; color: var(--c-dark);">{{ $item->name }}</strong>
                            <span style="font-size: 11px; color: {{ $item->tool_color }}; background: {{ $item->tool_color }}12; padding: 1px 8px; border-radius: 10px; white-space: nowrap;">{{ $item->tool_name }}</span>
                        </div>
                        <div style="font-size: 12px; color: var(--c-text-muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $item->preview }}</div>
                    </div>
                    {{-- Date + actions --}}
                    <div style="flex-shrink: 0; display: flex !important; align-items: center !important; gap: 8px;">
                        <span style="font-size: 11px; color: var(--c-text-muted);">{{ $item->created_at->format('d/m/Y') }}</span>
                        @if(Route::has('tools.show'))
                        <a href="{{ route('tools.show', $item->tool_slug) }}?edit={{ $item->public_id }}" class="btn btn-sm btn-outline-primary" style="border-radius: 6px; font-size: 11px; padding: 3px 10px;">{{ __('Charger') }}</a>
                        @endif
                        <button class="btn btn-sm btn-outline-danger js-delete-saved" data-api="{{ $item->api_path }}{{ $item->public_id }}" style="border-radius: 6px; font-size: 11px; padding: 3px 10px;">{{ __('Supprimer') }}</button>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    @endif

</div>

@push('scripts')
<script>
document.querySelectorAll('.js-delete-saved').forEach(function(btn) {
    btn.addEventListener('click', function() {
        if (!confirm('{{ __("Supprimer cette sauvegarde?") }}')) return;
        var row = this.closest('[x-show]');
        fetch(this.dataset.api, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '', 'Accept': 'application/json' } })
            .then(function() { if (row) row.remove(); });
    });
});
</script>
@endpush
@endsection
