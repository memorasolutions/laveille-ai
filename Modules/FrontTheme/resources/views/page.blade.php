@extends(fronttheme_layout())

@section('title', $page->title . ' - ' . config('app.name'))
@section('meta_description', Str::limit($page->meta_description ?? $page->excerpt ?? strip_tags($page->content), 160))

@section('breadcrumb')
    @include('fronttheme::partials.breadcrumb', ['breadcrumbTitle' => $page->title])
@endsection

@section('content')
    <!-- start wpo-blog-single-section -->
    <section class="wpo-blog-single-section section-padding">
        <div class="container">
            <div class="row">
                <div class="col col-lg-10 offset-lg-1">
                    <div class="wpo-blog-content">
                        <div class="post">
                            <h2>{{ $page->title }}</h2>
                            <div class="entry-details">
                                {!! $page->content !!}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- end wpo-blog-single-section -->
@endsection
