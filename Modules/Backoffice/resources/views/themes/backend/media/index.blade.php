<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::themes.backend.layouts.admin', ['title' => 'Médias', 'subtitle' => 'Bibliothèque'])

@section('content')

<nav class="page-breadcrumb" aria-label="Fil d'Ariane">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('Administration') }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ __('Médias') }}</li>
    </ol>
</nav>

<div class="card">
    <div class="p-4">
        @livewire('backoffice-media-table')
    </div>
</div>

@endsection
