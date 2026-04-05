<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
<!-- start of breadcumb-section -->
<div class="wpo-breadcumb-area">
    <div class="container">
        <div class="row">
            <div class="col-12">
                <div class="wpo-breadcumb-wrap">
                    <h2>{{ $breadcrumbTitle ?? '' }}</h2>
                    <ul>
                        <li><a href="{{ route('home') }}">{{ __('Accueil') }}</a></li>
                        @isset($breadcrumbItems)
                            @foreach($breadcrumbItems as $index => $item)
                                @if(!$loop->last)
                                    @php
                                        $breadcrumbRoutes = [
                                            __('Outils') => Route::has('tools.index') ? route('tools.index') : null,
                                            __('Blog') => Route::has('blog.index') ? route('blog.index') : null,
                                            __('Glossaire IA') => Route::has('dictionary.index') ? route('dictionary.index') : null,
                                            __('Répertoire techno') => Route::has('directory.index') ? route('directory.index') : null,
                                            __('FAQ') => Route::has('faq.index') ? route('faq.index') : null,
                                            __('Acronymes éducation') => Route::has('acronyms.index') ? route('acronyms.index') : null,
                                            __('Répertoire') => Route::has('directory.index') ? route('directory.index') : null,
                                            __('Glossaire') => Route::has('dictionary.index') ? route('dictionary.index') : null,
                                            __('Actualités') => Route::has('news.index') ? route('news.index') : null,
                                            __('Boutique') => Route::has('shop.index') ? route('shop.index') : null,
                                        ];
                                        $url = $breadcrumbRoutes[$item] ?? null;
                                    @endphp
                                    @if($url)
                                        <li><a href="{{ $url }}">{{ $item }}</a></li>
                                    @else
                                        <li><span>{{ $item }}</span></li>
                                    @endif
                                @else
                                    <li><span>{{ $item }}</span></li>
                                @endif
                            @endforeach
                        @else
                            <li><span>{{ $breadcrumbTitle ?? '' }}</span></li>
                        @endisset
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- end of wpo-breadcumb-section-->

@push('scripts')
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "BreadcrumbList",
    "itemListElement": [
        {
            "@@type": "ListItem",
            "position": 1,
            "name": "{{ __('Accueil') }}",
            "item": "{{ route('home') }}"
        }
        @if(!empty($breadcrumbItems))
            @foreach($breadcrumbItems as $index => $item)
                @if($index < count($breadcrumbItems) - 1)
                    ,{
                        "@@type": "ListItem",
                        "position": {{ $index + 2 }},
                        "name": "{{ $item }}",
                        "item": "{{ $breadcrumbRoutes[$item] ?? url()->current() }}"
                    }
                @else
                    ,{
                        "@@type": "ListItem",
                        "position": {{ $index + 2 }},
                        "name": "{{ $item }}",
                        "item": "{{ url()->current() }}"
                    }
                @endif
            @endforeach
        @endif
    ]
}
</script>
@endpush
