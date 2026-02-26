@extends('backoffice::layouts.admin', ['title' => 'Journaux', 'subtitle' => 'Application'])

@section('content')

<div class="card h-100 p-0 radius-12">
    <div class="card-header border-bottom bg-base py-16 px-24 d-flex align-items-center flex-wrap gap-3 justify-content-end">
        <form action="{{ route('admin.logs.clear') }}" method="POST" onsubmit="return confirm('Vider tous les journaux ?')">
            @csrf
            <button type="submit" class="btn btn-sm btn-danger-600 d-flex align-items-center gap-2 radius-8">
                <iconify-icon icon="solar:trash-bin-2-outline" class="icon text-xl"></iconify-icon>
                Vider les journaux
            </button>
        </form>
    </div>

    <div class="card-body border-bottom pb-16">
        <div class="d-flex flex-wrap gap-2">
            @foreach(['all', 'error', 'warning', 'info', 'debug'] as $lvl)
            <a href="{{ route('admin.logs', ['level' => $lvl]) }}"
               class="btn btn-sm {{ $level === $lvl ? 'btn-primary-600' : 'btn-outline-primary-600' }}">
                {{ ucfirst($lvl) }}
            </a>
            @endforeach
        </div>
    </div>

    <div class="card-body p-0">
        @if(count($entries) === 0)
            <div class="text-center py-40">
                <iconify-icon icon="solar:document-outline" class="text-6xl text-secondary-light mb-16"></iconify-icon>
                <p class="text-secondary-light">Aucune entrée de journal.</p>
                @if($level !== 'all')
                    <p class="text-sm text-secondary-light">Aucune entrée de niveau "{{ $level }}".</p>
                @endif
            </div>
        @else
            <div class="table-responsive scroll-sm">
                <table class="table bordered-table sm-table mb-0">
                    <thead>
                        <tr>
                            <th style="width:180px">Date</th>
                            <th style="width:100px">Niveau</th>
                            <th>Message</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($entries as $entry)
                        @php
                            $badgeClass = match($entry['level']) {
                                'ERROR', 'CRITICAL', 'ALERT', 'EMERGENCY' => 'bg-danger-focus text-danger-main',
                                'WARNING'  => 'bg-warning-focus text-warning-main',
                                'NOTICE', 'INFO'  => 'bg-primary-100 text-primary-600',
                                default    => 'bg-neutral-200 text-neutral-600',
                            };
                        @endphp
                        <tr>
                            <td class="text-sm text-secondary-light">{{ $entry['date'] }}</td>
                            <td>
                                <span class="badge {{ $badgeClass }}">{{ $entry['level'] }}</span>
                            </td>
                            <td class="text-sm" style="word-break:break-word;max-width:600px">{{ $entry['message'] }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="card-footer text-secondary-light text-sm">
                {{ count($entries) }} entrée(s) affichée(s)
            </div>
        @endif
    </div>
</div>

@endsection
