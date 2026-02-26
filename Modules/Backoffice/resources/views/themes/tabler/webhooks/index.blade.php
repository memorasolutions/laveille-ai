@extends('backoffice::layouts.admin', ['title' => 'Webhooks', 'subtitle' => 'Configuration'])
@section('content')
<div class="card">
    <div class="card-header"><h3 class="card-title">Gestion des Webhooks</h3></div>
    <div class="card-body">
        @livewire('backoffice-webhooks-manager')
    </div>
</div>
@endsection
