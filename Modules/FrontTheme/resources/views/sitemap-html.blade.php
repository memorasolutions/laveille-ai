<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends(fronttheme_layout())

@section('title', __('Plan du site') . ' - ' . config('app.name'))
@section('meta_description', __('Plan du site complet de La veille : blog, actualites, repertoire d\'outils IA, glossaire, acronymes, outils interactifs et ressources.'))

@section('breadcrumb')
    @include('fronttheme::partials.breadcrumb', ['breadcrumbTitle' => __('Plan du site')])
@endsection

@section('content')
    <h1 class="sr-only">{{ __('Plan du site') }} — {{ config('app.name') }}</h1>
    <section class="wpo-blog-single-section section-padding">
        <div class="container">
            <div class="row">
                <div class="col-md-8 col-md-offset-2">
                    @foreach($sections as $section)
                        <div class="panel panel-default" style="margin-bottom: 16px;">
                            <div class="panel-heading">
                                <h2 style="margin: 0; font-size: 1.3rem;">
                                    <a href="{{ url($section['url']) }}" style="color: #0B7285;">{{ $section['title'] }}</a>
                                </h2>
                                @if(!empty($section['description']))
                                    <p style="margin: 4px 0 0; color: #666; font-size: 0.9rem;">{{ $section['description'] }}</p>
                                @endif
                            </div>
                            @if(!empty($section['items']))
                                <div class="panel-body">
                                    <ul style="list-style: none; padding-left: 0; margin: 0;">
                                        @foreach($section['items'] as $item)
                                            <li style="padding: 3px 0;"><a href="{{ url($item['url']) }}">{{ $item['title'] }}</a></li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>
@endsection
