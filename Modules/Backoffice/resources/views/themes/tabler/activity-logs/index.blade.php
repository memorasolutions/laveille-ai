@extends('backoffice::layouts.admin', ['title' => 'Journaux d\'activité', 'subtitle' => 'Liste'])

@section('content')
<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between">
        <h3 class="card-title">Journaux d'activité</h3>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.activity-logs.export') }}" class="btn btn-outline-success btn-sm">
                <i class="ti ti-download me-1"></i> Export CSV
            </a>
            <form action="{{ route('admin.activity-logs.purge') }}" method="POST" onsubmit="return confirm('Purger les journaux de plus de 30 jours ?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-outline-danger btn-sm">
                    <i class="ti ti-trash me-1"></i> Purge +30j
                </button>
            </form>
        </div>
    </div>
    <div class="card-body">
        @livewire('backoffice-activity-logs-table')
    </div>
</div>
@endsection
