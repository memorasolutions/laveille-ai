@extends('backoffice::layouts.admin', ['title' => 'Médias', 'subtitle' => 'Bibliothèque'])

@section('content')

<div class="card h-100 p-0 radius-12">
    {{-- Principe ADHD: zero bruit visuel - pas de card-header vide --}}
    <div class="card-body p-24">
        @livewire('backoffice-media-table')
    </div>
</div>

@endsection
