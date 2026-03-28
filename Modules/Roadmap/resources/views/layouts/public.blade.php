<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends(function_exists('fronttheme_layout') ? fronttheme_layout() : 'layouts.app')

@section('title', __('Propositions de la communauté') . ' - ' . config('app.name'))

@section('breadcrumb')
    @if(View::exists('fronttheme::partials.breadcrumb'))
        @include('fronttheme::partials.breadcrumb', [
            'breadcrumbTitle' => __('Propositions'),
            'breadcrumbItems' => [__('Propositions')]
        ])
    @endif
@endsection

@section('content')
    <section class="section-padding" style="padding-top: 20px;">
        <div class="container">
            @if(session('success'))
                <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:12px;padding:14px 18px;margin-bottom:20px;color:#15803d;font-weight:500;">
                    {{ session('success') }}
                </div>
            @endif
            @yield('roadmap-content')
        </div>
    </section>
@endsection
