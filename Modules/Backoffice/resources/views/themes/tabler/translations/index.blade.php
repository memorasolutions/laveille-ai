@extends('backoffice::layouts.admin', ['title' => 'Traductions', 'subtitle' => 'Configuration'])
@section('content')
<div class="card">
    <div class="card-header"><h3 class="card-title">Gestion des traductions</h3></div>
    <div class="card-body">
        @livewire('backoffice-translations-manager')
    </div>
</div>
@endsection
