@extends('auth::layouts.user-frontend')

@section('title', __('Mes contributions') . ' - ' . config('app.name'))

@section('user-content')

<h2 style="font-family: var(--f-heading, inherit); font-weight: 700; margin: 0 0 5px;">
    🤝 {{ __('Mes contributions') }}
</h2>
<p style="color: var(--c-text-muted); margin: 0 0 20px;">{{ __('Vos suggestions, votes et ressources partagés avec la communauté.') }}</p>

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
                'suggestion' => ['icon' => '💡', 'label' => __('Suggestions'), 'color' => '#f59e0b'],
                'vote' => ['icon' => '👍', 'label' => __('Votes'), 'color' => '#0B7285'],
                'resource' => ['icon' => '📚', 'label' => __('Ressources'), 'color' => '#0891B2'],
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
            <div style="font-size: 2.5rem; margin-bottom: 12px;">🤝</div>
            <h3 style="font-family: var(--f-heading); margin-bottom: 8px;">{{ __('Aucune contribution') }}</h3>
            <p>{{ __('Visitez le répertoire, le glossaire ou la roadmap pour contribuer à la communauté.') }}</p>
        </div>
    @else
        <div style="display: flex !important; flex-direction: column !important; gap: 10px;">
            @foreach($items as $item)
            <div x-show="filter === 'all' || filter === '{{ $item->type }}'" x-transition
                 style="background: #fff; border: 1px solid #E5E7EB; border-radius: 10px; padding: 14px 18px;">
                <div style="display: flex !important; align-items: center !important; gap: 12px;">
                    {{-- Icône --}}
                    <div style="width: 40px; height: 40px; border-radius: 10px; background: {{ $item->color }}15; display: flex !important; align-items: center !important; justify-content: center !important; flex-shrink: 0; font-size: 18px;">
                        {{ $item->icon }}
                    </div>
                    {{-- Contenu --}}
                    <div style="flex: 1 !important; min-width: 0; overflow: hidden;">
                        <div style="display: flex !important; align-items: center !important; gap: 8px; margin-bottom: 2px;">
                            @if($item->link)
                                <a href="{{ $item->link }}" style="font-size: 14px; font-weight: 600; color: var(--c-dark); text-decoration: none;">{{ $item->name }}</a>
                            @else
                                <strong style="font-size: 14px; color: var(--c-dark);">{{ $item->name }}</strong>
                            @endif
                            <span style="font-size: 11px; color: {{ $item->color }}; background: {{ $item->color }}12; padding: 1px 8px; border-radius: 10px; white-space: nowrap;">{{ $item->label }}</span>
                        </div>
                        <div style="font-size: 12px; color: var(--c-text-muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $item->preview }}</div>
                    </div>
                    {{-- Statut + date --}}
                    <div style="flex-shrink: 0; text-align: right;">
                        @if($item->status === 'pending')
                            <span style="background: #fef3c7; color: #92400e; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 600;">{{ __('En attente') }}</span>
                        @elseif($item->status === 'approved')
                            <span style="background: #d1fae5; color: #065f46; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 600;">{{ __('Approuvée') }}</span>
                        @elseif($item->status === 'rejected')
                            <span style="background: #fee2e2; color: #991b1b; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 600;">{{ __('Rejetée') }}</span>
                        @elseif(isset($item->status_badge))
                            <span style="background: {{ $item->status_badge }}; color: #fff; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 600;">{{ $item->status_label }}</span>
                        @endif
                        <div style="font-size: 11px; color: var(--c-text-muted); margin-top: 4px;">{{ $item->created_at->format('d/m/Y') }}</div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    @endif

</div>

@endsection
