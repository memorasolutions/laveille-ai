<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends(fronttheme_layout())

@section('title', ($subject ?? __('Infolettre')) . ' - ' . config('app.name'))
@section('meta_description', __('Infolettre La veille — veille technologique IA'))

@section('breadcrumb')
    @include('fronttheme::partials.breadcrumb', [
        'breadcrumbTitle' => $subject ?? __('Infolettre'),
        'breadcrumbItems' => [__('Infolettre'), $subject ?? ''],
    ])
@endsection

@push('styles')
<style>
    .newsletter-embed {
        max-width: 620px;
        margin: 0 auto;
        background: #f4f4f4;
        padding: 10px;
        border-radius: 8px;
    }
    .newsletter-embed table {
        font-family: Arial, sans-serif;
    }
    .newsletter-embed img {
        max-width: 100%;
        height: auto;
    }
</style>
@endpush

@section('content')
    <section class="wpo-blog-single-section section-padding">
        <div class="container">
            <div class="row">
                <div class="col col-lg-8 col-12">
                    <div class="newsletter-embed">
                        {!! $emailHtml !!}
                    </div>
                </div>
                <div class="col col-lg-4 col-12">
                    @include('fronttheme::partials.sidebar')
                </div>
            </div>
        </div>
    </section>
@endsection
