<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-bs-theme="light">
<head>
    @include('backoffice::themes.tabler.components.head')
</head>
<body class="layout-fluid">
    <div class="page">
        @include('backoffice::themes.tabler.components.sidebar')

        <div class="page-wrapper">
            @include('backoffice::themes.tabler.components.navbar')

            {{-- Page header / Breadcrumb --}}
            <div class="page-header d-print-none">
                <div class="container-xl">
                    <div class="page-pretitle">
                        {{ $subtitle ?? 'Administration' }}
                    </div>
                    <h2 class="page-title">
                        {{ $title ?? 'Tableau de bord' }}
                    </h2>
                </div>
            </div>

            {{-- Flash messages --}}
            <div class="container-xl">
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <div class="d-flex">
                            <div><i class="ti ti-check me-2"></i></div>
                            <div>{{ session('success') }}</div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif
                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <div class="d-flex">
                            <div><i class="ti ti-alert-circle me-2"></i></div>
                            <div>{{ session('error') }}</div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif
            </div>

            {{-- Main content --}}
            <div class="page-body">
                <div class="container-xl">
                    @yield('content')
                </div>
            </div>

            @include('backoffice::themes.tabler.components.footer')
        </div>
    </div>

    {{-- Toast notifications --}}
    @includeIf('backoffice::partials.toast-notifications')

    @include('backoffice::themes.tabler.components.script')
</body>
</html>
