@extends('backoffice::layouts.admin', ['title' => __('Journal d\'activité'), 'subtitle' => __('Outils')])

@section('content')
    <div class="card">
        <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-3">
            <h5 class="card-title fw-semibold mb-0 d-flex align-items-center gap-2">
                <iconify-icon icon="solar:history-outline" class="text-primary-600"></iconify-icon>
                {{ __('Journal d\'activité') }}
            </h5>
            <div class="d-flex gap-2">
                <a href="{{ route('admin.activity-logs.export') }}" class="btn btn-sm btn-outline-primary-600 radius-8 d-flex align-items-center gap-1">
                    <iconify-icon icon="solar:download-outline"></iconify-icon> {{ __('Exporter CSV') }}
                </a>
                <form method="POST" action="{{ route('admin.activity-logs.purge') }}" onsubmit="return confirm('{{ __('Supprimer les entrées de plus de 30 jours ?') }}')">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-outline-danger-600 radius-8 d-flex align-items-center gap-1">
                        <iconify-icon icon="solar:trash-bin-minimalistic-outline"></iconify-icon> {{ __('Purger (+30j)') }}
                    </button>
                </form>
            </div>
        </div>
        @livewire('backoffice-activity-logs-table')
    </div>

    @if(session('success'))
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof notyf !== 'undefined') {
                notyf.success('{{ session('success') }}');
            }
        });
    </script>
    @endif
@endsection
