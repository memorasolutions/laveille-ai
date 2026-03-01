<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::themes.backend.layouts.admin', ['title' => 'Informations système', 'subtitle' => 'Diagnostic'])

@section('content')

<nav class="page-breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('Administration') }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ __('Informations système') }}</li>
    </ol>
</nav>

{{-- Stat cards --}}
<div class="row mb-4">
    <div class="col-sm-6 col-xl-3 mb-3">
        <div class="card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-circle bg-primary bg-opacity-10 d-flex align-items-center justify-content-center flex-shrink-0 text-primary"
                     style="width:48px;height:48px;min-width:48px;">
                    <i data-lucide="code" class="icon-md"></i>
                </div>
                <div>
                    <p class="text-muted small mb-1">PHP</p>
                    <h4 class="fw-bold mb-0">{{ $php['version'] }}</h4>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3 mb-3">
        <div class="card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-circle bg-success bg-opacity-10 d-flex align-items-center justify-content-center flex-shrink-0 text-success"
                     style="width:48px;height:48px;min-width:48px;">
                    <i data-lucide="server" class="icon-md"></i>
                </div>
                <div>
                    <p class="text-muted small mb-1">Laravel</p>
                    <h4 class="fw-bold mb-0">{{ $laravel['version'] }}</h4>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3 mb-3">
        <div class="card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-circle bg-warning bg-opacity-10 d-flex align-items-center justify-content-center flex-shrink-0 text-warning"
                     style="width:48px;height:48px;min-width:48px;">
                    <i data-lucide="globe" class="icon-md"></i>
                </div>
                <div>
                    <p class="text-muted small mb-1">Environnement</p>
                    <h4 class="fw-bold mb-0">{{ $laravel['environment'] }}</h4>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3 mb-3">
        <div class="card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-circle bg-info bg-opacity-10 d-flex align-items-center justify-content-center flex-shrink-0 text-info"
                     style="width:48px;height:48px;min-width:48px;">
                    <i data-lucide="layout-grid" class="icon-md"></i>
                </div>
                <div>
                    <p class="text-muted small mb-1">Modules actifs</p>
                    <h4 class="fw-bold mb-0">{{ count($modules) }}</h4>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- PHP + Laravel detail cards --}}
<div class="row mb-4">
    <div class="col-xl-6 mb-4">
        <div class="card h-100">
            <div class="card-header py-3 px-4 border-bottom">
                <h4 class="fw-semibold mb-0 d-flex align-items-center gap-2">
                    <i data-lucide="code" class="icon-sm text-primary"></i>
                    PHP
                </h4>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table mb-0">
                        <tbody>
                            @foreach([
                                ['Version', $php['version']],
                                ['SAPI', $php['sapi']],
                                ['Memory limit', $php['memory_limit']],
                                ['Max execution time', $php['max_execution_time'] . 's'],
                                ['Upload max filesize', $php['upload_max_filesize']],
                                ['Post max size', $php['post_max_size']],
                            ] as [$label, $value])
                            <tr>
                                <td class="py-3 px-4 fw-semibold text-body w-50">{{ $label }}</td>
                                <td class="py-3 px-4 text-muted small">{{ $value }}</td>
                            </tr>
                            @endforeach
                            <tr>
                                <td class="py-3 px-4 fw-semibold text-body">OPcache</td>
                                <td class="py-3 px-4">
                                    @if($php['opcache'])
                                        <span class="badge bg-success bg-opacity-10 text-success">Activé</span>
                                    @else
                                        <span class="badge bg-danger bg-opacity-10 text-danger">Désactivé</span>
                                    @endif
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-6 mb-4">
        <div class="card h-100">
            <div class="card-header py-3 px-4 border-bottom">
                <h4 class="fw-semibold mb-0 d-flex align-items-center gap-2">
                    <i data-lucide="server" class="icon-sm text-success"></i>
                    Laravel
                </h4>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table mb-0">
                        <tbody>
                            @foreach([
                                ['Version', $laravel['version']],
                                ['Environnement', $laravel['environment']],
                                ['Timezone', $laravel['timezone']],
                                ['Locale', $laravel['locale']],
                                ['Cache', $laravel['cache_driver']],
                                ['Session', $laravel['session_driver']],
                                ['Queue', $laravel['queue_driver']],
                            ] as [$label, $value])
                            <tr>
                                <td class="py-3 px-4 fw-semibold text-body w-50">{{ $label }}</td>
                                <td class="py-3 px-4 text-muted small">{{ $value }}</td>
                            </tr>
                            @endforeach
                            <tr>
                                <td class="py-3 px-4 fw-semibold text-body">Debug</td>
                                <td class="py-3 px-4">
                                    @if($laravel['debug'])
                                        <span class="badge bg-danger bg-opacity-10 text-danger">Activé</span>
                                    @else
                                        <span class="badge bg-success bg-opacity-10 text-success">Désactivé</span>
                                    @endif
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Serveur + Modules actifs --}}
<div class="row mb-4">
    <div class="col-xl-6 mb-4">
        <div class="card h-100">
            <div class="card-header py-3 px-4 border-bottom">
                <h4 class="fw-semibold mb-0 d-flex align-items-center gap-2">
                    <i data-lucide="hard-drive" class="icon-sm text-body-secondary"></i>
                    Serveur
                </h4>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table mb-0">
                        <tbody>
                            <tr>
                                <td class="py-3 px-4 fw-semibold text-body w-33">OS</td>
                                <td class="py-3 px-4 text-muted small">{{ $server['os'] }}</td>
                            </tr>
                            <tr>
                                <td class="py-3 px-4 fw-semibold text-body">Hostname</td>
                                <td class="py-3 px-4 text-muted small">{{ $server['hostname'] }}</td>
                            </tr>
                            <tr>
                                <td class="py-3 px-4 fw-semibold text-body">Disque</td>
                                <td class="py-3 px-4">
                                    @php
                                        $free = number_format($server['disk_free'] / 1073741824, 1);
                                        $total = number_format($server['disk_total'] / 1073741824, 1);
                                        $usedPct = $server['disk_total'] > 0
                                            ? round((1 - $server['disk_free'] / $server['disk_total']) * 100)
                                            : 0;
                                        $barColor = $usedPct > 90 ? 'bg-danger' : ($usedPct > 75 ? 'bg-warning' : 'bg-primary');
                                    @endphp
                                    <p class="small text-muted mb-2">{{ $free }} Go libre / {{ $total }} Go total</p>
                                    <div class="progress mb-1" style="height:6px;">
                                        <div class="progress-bar {{ $barColor }}"
                                             role="progressbar"
                                             style="width: {{ $usedPct }}%"
                                             aria-valuenow="{{ $usedPct }}"
                                             aria-valuemin="0"
                                             aria-valuemax="100"></div>
                                    </div>
                                    <p class="text-muted small mb-0">{{ $usedPct }}% utilisé</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-6 mb-4">
        <div class="card h-100">
            <div class="card-header py-3 px-4 border-bottom">
                <h4 class="fw-semibold mb-0 d-flex align-items-center gap-2">
                    <i data-lucide="layout-grid" class="icon-sm text-info"></i>
                    Modules actifs ({{ count($modules) }})
                </h4>
            </div>
            <div class="card-body">
                <div class="d-flex flex-wrap gap-2">
                    @foreach($modules as $module => $active)
                        <span class="badge bg-primary bg-opacity-10 text-primary">{{ $module }}</span>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Extensions PHP --}}
<div class="card mb-4">
    <div class="card-header py-3 px-4 border-bottom">
        <h4 class="fw-semibold mb-0 d-flex align-items-center gap-2">
            <i data-lucide="zap" class="icon-sm text-body-secondary"></i>
            Extensions PHP ({{ count($php['extensions']) }})
        </h4>
    </div>
    <div class="card-body">
        <div class="d-flex flex-wrap gap-2">
            @php $extensions = $php['extensions']; sort($extensions); @endphp
            @foreach($extensions as $ext)
                <span class="badge bg-secondary bg-opacity-10 text-secondary">{{ $ext }}</span>
            @endforeach
        </div>
    </div>
</div>

@endsection
