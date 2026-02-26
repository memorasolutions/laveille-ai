@extends('backoffice::layouts.admin', ['title' => "Journaux d'activité", 'subtitle' => 'Liste'])

@section('content')

<div class="card h-100 p-0 radius-12">
    <div class="card-header border-bottom bg-base py-16 px-24 d-flex align-items-center flex-wrap gap-3 justify-content-end">
        <div class="d-flex gap-2">
            <a href="{{ route('admin.activity-logs.export') }}" class="btn btn-sm btn-outline-success-600 radius-8 d-inline-flex align-items-center gap-1">
                <iconify-icon icon="solar:download-minimalistic-outline" class="icon text-xl"></iconify-icon> Exporter CSV
            </a>
            <form action="{{ route('admin.activity-logs.purge') }}" method="POST" onsubmit="return confirm('Supprimer les entrées de plus de 30 jours ?')">
                @csrf @method('DELETE')
                <button type="submit" class="btn btn-sm btn-outline-danger-600 radius-8 d-inline-flex align-items-center gap-1">
                    <iconify-icon icon="solar:trash-bin-trash-outline" class="icon text-xl"></iconify-icon> Purger (+30j)
                </button>
            </form>
        </div>
    </div>
    <div class="card-body p-24">
        @livewire('backoffice-activity-logs-table')
    </div>
</div>

@endsection
