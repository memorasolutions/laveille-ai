<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends(fronttheme_layout())

@section('title', __('Nos formations').' - '.config('app.name'))

@if(config('catalog.noindex'))
    @section('page_noindex', true)
@endif

@section('breadcrumb')
    @include('fronttheme::partials.breadcrumb', ['breadcrumbTitle' => __('Nos formations')])
@endsection

@push('styles')
<style>
    .catalog { padding: 32px 0; background: #fff; color: #111827; }
    .catalog h1, .catalog h2, .catalog h3 { color: #111827; }
    .catalog .catalog-filters { display: flex; flex-wrap: wrap; gap: 12px; margin: 24px 0; }
    .catalog .catalog-filters[x-cloak] { display: none !important; }
    .catalog .catalog-filter { border: 1px solid #134e4a; border-radius: 6px; padding: 8px 16px; background: #fff; color: #134e4a; cursor: pointer; }
    .catalog .catalog-filter[aria-pressed="true"] { background: #134e4a; color: #fff; }
    .catalog .catalog-price { position: absolute; top: 12px; left: 12px; z-index: 2; pointer-events: none; padding: 6px 14px; border-radius: 999px; background: #e7f6ec; color: #0f5132; font-size: 14px; font-weight: 600; box-shadow: 0 1px 3px #00000026; }
    .catalog .grid { margin-top: 24px; }
    .catalog .grid { display: grid; grid-template-columns: minmax(0, 1fr); gap: 24px; }
    .catalog article { background: #fff; border: 1px solid #f3f4f6; border-radius: 12px; overflow: hidden; box-shadow: 0 1px 2px #0000000d; transition: box-shadow .2s; }
    .catalog article:hover { box-shadow: 0 4px 6px #0000001a; }
    .catalog .catalog-thumb-link { display: block; }
    .catalog .catalog-image { width: 100%; height: 192px; object-fit: cover; }
    .catalog .catalog-placeholder { height: 192px; display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, #134e4a, #115e59); color: #fff; }
    .catalog .catalog-placeholder svg { width: 64px; height: 64px; }
    .catalog .catalog-card-body { padding: 20px; }
    .catalog .catalog-badge { display: inline-block; padding: 4px 8px; border-radius: 4px; background: #f0fdfa; color: #134e4a; font-size: 14px; }
    .catalog .catalog-card-title { margin: 12px 0; font-size: 20px; }
    .catalog p { color: #374151; }
    .catalog a { color: #134e4a; text-decoration: underline; }
    .catalog a:hover { color: #111827; }
    .catalog a:focus-visible, .catalog button:focus-visible { outline: 3px solid #134e4a; outline-offset: 4px; }
    @media (min-width: 768px) { .catalog .grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
    @media (min-width: 1024px) { .catalog .grid { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
</style>
@endpush

@section('content')
<div class="catalog" x-data="{ filter: 'all' }">
    <div class="container">
        <h1>{{ __('Nos formations') }}</h1>
        @if(empty($categories))
            <p>{{ __('Les formations seront bientôt disponibles.') }}</p>
        @else
            <div class="catalog-filters" role="group" aria-label="{{ __('Filtrer par catégorie') }}" x-cloak>
                <button type="button" class="catalog-filter" data-filter="all" aria-pressed="true"
                        x-bind:aria-pressed="filter === 'all'" x-on:click="filter = $el.dataset.filter">{{ __('Toutes') }}</button>
                @foreach($categories as $group)
                    <button type="button" class="catalog-filter" data-filter="{{ $group['category'] }}" aria-pressed="false"
                            x-bind:aria-pressed="filter === $el.dataset.filter" x-on:click="filter = $el.dataset.filter">{{ $group['category'] }}</button>
                @endforeach
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($categories as $group)
                    @foreach($group['courses'] as $course)
                        <article data-category="{{ $group['category'] }}" x-show="filter === 'all' || filter === $el.dataset.category" class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-md transition">
                            <a href="{{ $course['course_url'] }}" class="catalog-thumb-link" aria-label="{{ __('Voir la formation').' : '.$course['fullname'] }}">
                                @if($course['image_local'])
                                    <img src="{{ $course['image_local'] }}" alt="{{ $course['fullname'] }}" class="catalog-image w-full h-48 object-cover" loading="lazy">
                                @else
                                    <div class="catalog-placeholder">
                                        <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                            <path d="M3 4h6a3 3 0 0 1 3 3v14a4 4 0 0 0-4-3H3V4Zm18 0h-6a3 3 0 0 0-3 3v14a4 4 0 0 1 4-3h5V4Z" />
                                        </svg>
                                    </div>
                                @endif
                            </a>
                            <div class="catalog-card-body p-5">
                                <span class="catalog-badge">{{ $group['category'] }}</span>
                                <span class="catalog-price">{{ $course['price_display'] }}</span>
                                <h2 class="catalog-card-title">{{ $course['fullname'] }}</h2>
                                <p>{{ \Illuminate\Support\Str::limit(strip_tags($course['summary']), 140) }}</p>
                                <a href="{{ $course['course_url'] }}" aria-label="{{ __('Voir la formation').' : '.$course['fullname'] }}">{{ __('Voir la formation') }}</a>
                            </div>
                        </article>
                    @endforeach
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection
