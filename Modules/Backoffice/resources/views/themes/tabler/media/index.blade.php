@extends('backoffice::layouts.admin', ['title' => 'Médias', 'subtitle' => 'Contenu'])

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Gestion des médias</h3>
    </div>
    <div class="card-body">
        @livewire('backoffice-media-table')
    </div>
</div>
@endsection
