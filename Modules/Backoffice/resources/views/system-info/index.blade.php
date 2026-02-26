@extends('backoffice::layouts.admin', ['title' => 'Informations système', 'subtitle' => 'Diagnostic'])

@section('content')

<div class="row gy-4">
    {{-- Summary cards --}}
    <div class="col-md-3">
        <div class="card radius-12 h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="w-40-px h-40-px bg-primary-100 text-primary-600 d-flex align-items-center justify-content-center rounded-circle flex-shrink-0">
                    <iconify-icon icon="solar:code-square-outline" class="icon text-xl"></iconify-icon>
                </div>
                <div>
                    <h6 class="mb-4 text-secondary-light">PHP</h6>
                    <h4 class="fw-bold mb-0">{{ $php['version'] }}</h4>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card radius-12 h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="w-40-px h-40-px bg-success-100 text-success-600 d-flex align-items-center justify-content-center rounded-circle flex-shrink-0">
                    <iconify-icon icon="solar:layers-outline" class="icon text-xl"></iconify-icon>
                </div>
                <div>
                    <h6 class="mb-4 text-secondary-light">Laravel</h6>
                    <h4 class="fw-bold mb-0">{{ $laravel['version'] }}</h4>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card radius-12 h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="w-40-px h-40-px bg-warning-100 text-warning-600 d-flex align-items-center justify-content-center rounded-circle flex-shrink-0">
                    <iconify-icon icon="solar:globe-outline" class="icon text-xl"></iconify-icon>
                </div>
                <div>
                    <h6 class="mb-4 text-secondary-light">Environnement</h6>
                    <h4 class="fw-bold mb-0">{{ $laravel['environment'] }}</h4>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card radius-12 h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="w-40-px h-40-px bg-info-100 text-info-600 d-flex align-items-center justify-content-center rounded-circle flex-shrink-0">
                    <iconify-icon icon="solar:widget-4-outline" class="icon text-xl"></iconify-icon>
                </div>
                <div>
                    <h6 class="mb-4 text-secondary-light">Modules actifs</h6>
                    <h4 class="fw-bold mb-0">{{ count($modules) }}</h4>
                </div>
            </div>
        </div>
    </div>

    {{-- PHP + Laravel details --}}
    <div class="col-md-6">
        <div class="card radius-12 h-100">
            <div class="card-header">
                <h6 class="mb-0 d-flex align-items-center gap-2">
                    <iconify-icon icon="solar:code-square-outline" class="icon text-xl"></iconify-icon> PHP
                </h6>
            </div>
            <div class="card-body p-0 scroll-sm">
                <table class="table bordered-table sm-table mb-0">
                    <tbody>
                        <tr><td class="fw-semibold">Version</td><td>{{ $php['version'] }}</td></tr>
                        <tr><td class="fw-semibold">SAPI</td><td>{{ $php['sapi'] }}</td></tr>
                        <tr><td class="fw-semibold">Memory limit</td><td>{{ $php['memory_limit'] }}</td></tr>
                        <tr><td class="fw-semibold">Max execution time</td><td>{{ $php['max_execution_time'] }}s</td></tr>
                        <tr><td class="fw-semibold">Upload max filesize</td><td>{{ $php['upload_max_filesize'] }}</td></tr>
                        <tr><td class="fw-semibold">Post max size</td><td>{{ $php['post_max_size'] }}</td></tr>
                        <tr>
                            <td class="fw-semibold">OPcache</td>
                            <td>
                                @if($php['opcache'])
                                    <span class="badge bg-success-100 text-success-600">Activé</span>
                                @else
                                    <span class="badge bg-danger-100 text-danger-600">Désactivé</span>
                                @endif
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card radius-12 h-100">
            <div class="card-header">
                <h6 class="mb-0 d-flex align-items-center gap-2">
                    <iconify-icon icon="solar:layers-outline" class="icon text-xl"></iconify-icon> Laravel
                </h6>
            </div>
            <div class="card-body p-0 scroll-sm">
                <table class="table bordered-table sm-table mb-0">
                    <tbody>
                        <tr><td class="fw-semibold">Version</td><td>{{ $laravel['version'] }}</td></tr>
                        <tr><td class="fw-semibold">Environnement</td><td>{{ $laravel['environment'] }}</td></tr>
                        <tr>
                            <td class="fw-semibold">Debug</td>
                            <td>
                                @if($laravel['debug'])
                                    <span class="badge bg-danger-100 text-danger-600">Activé</span>
                                @else
                                    <span class="badge bg-success-100 text-success-600">Désactivé</span>
                                @endif
                            </td>
                        </tr>
                        <tr><td class="fw-semibold">Timezone</td><td>{{ $laravel['timezone'] }}</td></tr>
                        <tr><td class="fw-semibold">Locale</td><td>{{ $laravel['locale'] }}</td></tr>
                        <tr><td class="fw-semibold">Cache</td><td>{{ $laravel['cache_driver'] }}</td></tr>
                        <tr><td class="fw-semibold">Session</td><td>{{ $laravel['session_driver'] }}</td></tr>
                        <tr><td class="fw-semibold">Queue</td><td>{{ $laravel['queue_driver'] }}</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Server + Modules --}}
    <div class="col-md-6">
        <div class="card radius-12 h-100">
            <div class="card-header">
                <h6 class="mb-0 d-flex align-items-center gap-2">
                    <iconify-icon icon="solar:server-outline" class="icon text-xl"></iconify-icon> Serveur
                </h6>
            </div>
            <div class="card-body p-0 scroll-sm">
                <table class="table bordered-table sm-table mb-0">
                    <tbody>
                        <tr><td class="fw-semibold">OS</td><td>{{ $server['os'] }}</td></tr>
                        <tr><td class="fw-semibold">Hostname</td><td>{{ $server['hostname'] }}</td></tr>
                        <tr>
                            <td class="fw-semibold">Disque</td>
                            <td>
                                @php
                                    $free = number_format($server['disk_free'] / 1073741824, 1);
                                    $total = number_format($server['disk_total'] / 1073741824, 1);
                                    $usedPct = $server['disk_total'] > 0
                                        ? round((1 - $server['disk_free'] / $server['disk_total']) * 100)
                                        : 0;
                                @endphp
                                <div class="mb-4">{{ $free }} Go libre / {{ $total }} Go total</div>
                                <div class="progress" style="height: 8px;">
                                    <div class="progress-bar {{ $usedPct > 90 ? 'bg-danger' : ($usedPct > 75 ? 'bg-warning' : 'bg-primary') }}"
                                         role="progressbar"
                                         style="width: {{ $usedPct }}%"
                                         aria-valuenow="{{ $usedPct }}" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                                <small class="text-secondary-light">{{ $usedPct }}% utilisé</small>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card radius-12 h-100">
            <div class="card-header">
                <h6 class="mb-0 d-flex align-items-center gap-2">
                    <iconify-icon icon="solar:widget-4-outline" class="icon text-xl"></iconify-icon> Modules actifs ({{ count($modules) }})
                </h6>
            </div>
            <div class="card-body">
                <div class="d-flex flex-wrap gap-2">
                    @foreach($modules as $module => $active)
                        <span class="badge bg-primary-100 text-primary-600">{{ $module }}</span>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    {{-- PHP Extensions --}}
    <div class="col-12">
        <div class="card radius-12">
            <div class="card-header">
                <h6 class="mb-0 d-flex align-items-center gap-2">
                    <iconify-icon icon="solar:plug-circle-outline" class="icon text-xl"></iconify-icon>
                    Extensions PHP ({{ count($php['extensions']) }})
                </h6>
            </div>
            <div class="card-body">
                <div class="d-flex flex-wrap gap-2">
                    @php $extensions = $php['extensions']; sort($extensions); @endphp
                    @foreach($extensions as $ext)
                        <span class="badge bg-secondary-100 text-secondary-600">{{ $ext }}</span>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
