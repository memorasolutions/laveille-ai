<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::themes.backend.layouts.admin', ['title' => 'Journaux', 'subtitle' => 'Application'])

@section('content')

<nav class="page-breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('Administration') }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ __('Journaux') }}</li>
    </ol>
</nav>

<div class="card">
    <div class="card-header py-3 px-4 border-bottom">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
            <div class="d-flex flex-wrap gap-2">
                @foreach(['all', 'error', 'warning', 'info', 'debug'] as $lvl)
                <a href="{{ route('admin.logs', ['level' => $lvl]) }}"
                   class="btn btn-sm {{ $level === $lvl ? 'btn-primary' : 'btn-outline-primary' }}">
                    {{ ucfirst($lvl) }}
                </a>
                @endforeach
            </div>
            <form action="{{ route('admin.logs.clear') }}" method="POST"
                  onsubmit="return confirm('{{ __('Vider tous les journaux ?') }}')">
                @csrf
                <button type="submit"
                        class="btn btn-danger btn-sm d-inline-flex align-items-center gap-2">
                    <i data-lucide="trash-2" class="icon-sm"></i>
                    {{ __('Vider les journaux') }}
                </button>
            </form>
        </div>
    </div>
    <div class="card-body p-0">
        @if(count($entries) === 0)
            <div class="text-center py-5">
                <i data-lucide="file-text" class="text-muted mb-3" style="width:64px;height:64px;opacity:.3;"></i>
                <p class="text-muted">{{ __('Aucune entrée de journal.') }}</p>
                @if($level !== 'all')
                    <p class="text-muted small">{{ __('Aucune entrée de niveau') }} "{{ $level }}".</p>
                @endif
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th class="py-3 px-4 fw-semibold text-body" style="width:180px;">{{ __('Date') }}</th>
                            <th class="py-3 px-4 fw-semibold text-body" style="width:120px;">{{ __('Niveau') }}</th>
                            <th class="py-3 px-4 fw-semibold text-body">{{ __('Message') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($entries as $entry)
                        @php
                            $badgeClass = match($entry['level']) {
                                'ERROR', 'CRITICAL', 'ALERT', 'EMERGENCY' => 'bg-danger bg-opacity-10 text-danger',
                                'WARNING'  => 'bg-warning bg-opacity-10 text-warning',
                                'NOTICE', 'INFO'  => 'bg-primary bg-opacity-10 text-primary',
                                default    => 'bg-secondary bg-opacity-10 text-secondary',
                            };
                        @endphp
                        <tr>
                            <td class="py-3 px-4 text-muted small">{{ $entry['date'] }}</td>
                            <td class="py-3 px-4">
                                <span class="badge {{ $badgeClass }}">
                                    {{ $entry['level'] }}
                                </span>
                            </td>
                            <td class="py-3 px-4 small text-muted" style="max-width:500px;overflow-wrap:break-word;word-break:break-word;">
                                {{ $entry['message'] }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="card-footer">
                <span class="text-muted small">{{ count($entries) }} {{ __('entrée(s) affichée(s)') }}</span>
            </div>
        @endif
    </div>
</div>

@endsection
