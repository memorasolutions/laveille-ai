<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::themes.backend.layouts.admin', ['title' => "Journaux d'activité", 'subtitle' => 'Liste'])

@section('content')

<nav class="page-breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('Administration') }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ __("Journal d'activité") }}</li>
    </ol>
</nav>

<div class="card">
    <div class="card-header py-3 px-4 border-bottom">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
            <h4 class="fw-bold mb-0 d-flex align-items-center gap-2"><i data-lucide="scroll-text" class="icon-md text-primary"></i>{{ __("Journaux d'activité") }}</h4>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <a href="{{ route('admin.activity-logs.export') }}"
                   class="btn btn-sm btn-outline-success d-inline-flex align-items-center gap-2">
                    <i data-lucide="download" class="icon-sm"></i>
                    {{ __('Exporter CSV') }}
                </a>
                <form action="{{ route('admin.activity-logs.purge') }}" method="POST"
                      onsubmit="return confirm('{{ __('Supprimer les entrées de plus de 30 jours ?') }}')">
                    @csrf @method('DELETE')
                    <button type="submit"
                            class="btn btn-sm btn-outline-danger d-inline-flex align-items-center gap-2">
                        <i data-lucide="trash-2" class="icon-sm"></i>
                        {{ __('Purger (+30j)') }}
                    </button>
                </form>
            </div>
        </div>
    </div>
    <div class="card-body p-4">
        @livewire('backoffice-activity-logs-table')
    </div>
</div>

@endsection
