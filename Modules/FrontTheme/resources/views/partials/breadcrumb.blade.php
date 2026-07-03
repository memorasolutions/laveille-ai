<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
<!-- start of breadcumb-section -->
<div class="wpo-breadcumb-area">
    <div class="container">
        <div class="row">
            <div class="col-12">
                <div class="wpo-breadcumb-wrap">
                    <h2>{{ $breadcrumbTitle ?? '' }}</h2>
                    <nav aria-label="{{ __('Fil d\'Ariane') }}">
                    <ul>
                        <li><a href="{{ route('home') }}">{{ __('Accueil') }}</a></li>
                        @isset($breadcrumbItems)
                            @foreach($breadcrumbItems as $index => $item)
                                @if(!$loop->last)
                                    @php
                                        $breadcrumbRoutes = [
                                            __('Académie') => Route::has('academy.dashboard') ? route('academy.dashboard') : null,
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
                                            __("L'IA pour les PME") => Route::has('pillar.ia-pme') ? route('pillar.ia-pme') : null,
                                            __("L'IA en éducation") => Route::has('pillar.ia-education') ? route('pillar.ia-education') : null,
                                            __("L'IA pour les développeurs") => Route::has('pillar.ia-dev') ? route('pillar.ia-dev') : null,
                                            __('IA générative') => Route::has('pillar.ia-generative') ? route('pillar.ia-generative') : null,
                                            __('Faire sa veille IA') => Route::has('pillar.veille-ia') ? route('pillar.veille-ia') : null,
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
                    </nav>
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
