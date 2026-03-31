{{-- Vue dynamique pour pages légales éditables admin --}}
@extends(fronttheme_layout())

@section('title', $page->title . ' - ' . config('app.name'))
@section('meta_description', Str::limit(strip_tags($page->content), 160))

@section('breadcrumb')
    @include('fronttheme::partials.breadcrumb', ['breadcrumbTitle' => $page->title])
@endsection

@section('content')
<section class="wpo-blog-single-section section-padding">
    <div class="container">
        <div class="row">
            <div class="col col-lg-10 offset-lg-1">
                <h1 style="font-family: var(--f-heading, inherit); font-weight: 700; margin-bottom: 8px;">{{ $page->title }}</h1>
                @if($page->updated_at)
                    <p class="text-muted" style="font-size: 13px; margin-bottom: 24px;">
                        {{ __('Dernière mise à jour') }} : {{ $page->updated_at->format('d/m/Y') }}
                    </p>
                @endif
                <div class="legal-content" style="line-height: 1.8; font-size: 15px;">
                    {!! $page->content !!}
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
