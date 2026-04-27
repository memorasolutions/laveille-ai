<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends(fronttheme_layout())

@section('title', $tool->name . ' - ' . __('Outils') . ' - ' . config('app.name'))
@section('meta_description', Str::limit(strip_tags($tool->description ?? ''), 160))
@section('og_type', 'article')
@section('og_image', $ogImage)

@section('breadcrumb')
    @include('fronttheme::partials.breadcrumb', [
        'breadcrumbTitle' => $tool->name,
        'breadcrumbItems' => [__('Outils'), $tool->name]
    ])
@endsection

@section('content')
    <section class="wpo-blog-single-section section-padding">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-10 col-12">
                    <div class="card shadow-sm" style="border-radius: var(--r-base);">
                        <div class="card-body p-4 p-md-5">
                            <div class="d-flex justify-content-between align-items-start gap-2">
                                <h1 style="font-family: var(--f-heading); font-weight: 800; color: var(--c-dark);">{{ $tool->name }}</h1>
                                @include('tools::partials.share-btn', ['tool' => $tool])
                            </div>
                            <p class="text-muted mb-4">{{ $tool->description }}</p>
                            <div class="alert alert-info">
                                {{ __('Cet outil sera bientôt disponible.') }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
