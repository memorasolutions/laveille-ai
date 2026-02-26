@extends('backoffice::layouts.admin', ['title' => 'Recherche', 'subtitle' => 'Résultats'])
@section('content')
<div class="card">
    <div class="card-header"><h3 class="card-title">Résultats pour "{{ $query ?? '' }}"</h3></div>
    <div class="card-body">
        @forelse($results ?? [] as $section => $items)
        <h4 class="mt-3 mb-2">{{ $section }}</h4>
        <div class="list-group list-group-flush">
            @foreach($items as $item)
            <a href="{{ $item['url'] ?? '#' }}" class="list-group-item list-group-item-action">
                <div class="d-flex align-items-center">
                    <div><strong>{{ $item['title'] ?? '' }}</strong><br><small class="text-muted">{{ $item['subtitle'] ?? '' }}</small></div>
                </div>
            </a>
            @endforeach
        </div>
        @empty
        <p class="text-muted text-center">Aucun résultat trouvé</p>
        @endforelse
    </div>
</div>
@endsection
