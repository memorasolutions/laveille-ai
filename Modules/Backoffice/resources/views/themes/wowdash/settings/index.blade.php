@extends('backoffice::layouts.admin', ['title' => 'Paramètres', 'subtitle' => 'Configuration'])

@section('content')

<div class="d-flex align-items-center justify-content-end mb-24">
    <a href="{{ route('admin.settings.create') }}" class="btn btn-primary-600 text-sm btn-sm px-16 py-10 radius-8 d-flex align-items-center gap-2">
        <iconify-icon icon="ic:baseline-plus" class="icon text-xl line-height-1"></iconify-icon> Ajouter un paramètre
    </a>
</div>

@livewire('backoffice-settings-manager')

@endsection
