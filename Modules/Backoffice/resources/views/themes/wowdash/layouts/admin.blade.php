<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light">

@include('backoffice::components.head', ['title' => $title ?? ''])

<body>
    {{-- Override WowDash h1 clamp (trop gros pour un backoffice) --}}
    {{-- Principe: hiérarchie typographique - titre page = 2rem, pas le clamp géant WowDash --}}
    <style>.dashboard-main-body h1 { font-size: 2rem !important; }</style>

    @include('backoffice::components.sidebar')

    <main class="dashboard-main">

        @include('backoffice::components.navbar')

        <div class="dashboard-main-body">

            @include('backoffice::components.breadcrumb', [
                'title' => $title ?? '',
                'subtitle' => $subtitle ?? '',
            ])

            {{-- Flash messages --}}
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show mb-24" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show mb-24" role="alert">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @yield('content')

        </div>

        @include('backoffice::components.footer')

    </main>

    @include('backoffice::partials.toast-notifications')

    @include('backoffice::components.script')

</body>

</html>
