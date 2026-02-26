@extends('backoffice::layouts.admin', ['title' => 'Feature Flags', 'subtitle' => 'Configuration'])

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Feature Flags</h3>
    </div>
    <div class="card-body">
        @livewire('backoffice-feature-flags-table')
    </div>
</div>
@endsection
