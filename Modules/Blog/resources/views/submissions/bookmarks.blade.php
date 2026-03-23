<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends(fronttheme_layout())

@section('title', __('Mes favoris') . ' - ' . config('app.name'))

@section('breadcrumb')
    @include('fronttheme::partials.breadcrumb', ['breadcrumbTitle' => __('Mes favoris')])
@endsection

@section('content')
<div class="container" style="padding: 20px 0 60px; min-height: 50vh;">
    <div style="max-width: 800px; margin: 0 auto;">
        <h1 style="font-family: var(--f-heading); font-weight: 700; color: var(--c-dark); margin: 0 0 24px;">🔖 {{ __('Mes favoris') }}</h1>

        @php $totalBookmarks = $bookmarks->flatten()->count(); @endphp

        @if($totalBookmarks === 0)
            <div style="text-align: center; padding: 60px 20px; background: #F9FAFB; border-radius: var(--r-base); border: 1px dashed #D1D5DB;">
                <div style="font-size: 48px; margin-bottom: 12px;">🔖</div>
                <h3 style="font-family: var(--f-heading); color: var(--c-dark);">{{ __('Aucun favori pour le moment') }}</h3>
                <p style="color: #6B7280;">{{ __('Sauvegardez des articles en cliquant sur le bouton favori.') }}</p>
                <a href="{{ route('blog.index') }}" style="background: var(--c-primary); color: #fff; padding: 10px 24px; border-radius: var(--r-btn); font-weight: 600; text-decoration: none; display: inline-block; margin-top: 12px;">{{ __('Parcourir le blog') }}</a>
            </div>
        @else
            @foreach($bookmarks as $type => $items)
                @php
                    $typeName = match($type) {
                        'Modules\\Blog\\Models\\Article' => __('Articles'),
                        'Modules\\Directory\\Models\\Tool' => __('Outils IA'),
                        'Modules\\Dictionary\\Models\\Term' => __('Termes du glossaire'),
                        'Modules\\Acronyms\\Models\\Acronym' => __('Acronymes'),
                        default => __('Favoris'),
                    };
                @endphp
                <h2 style="font-family: var(--f-heading); font-weight: 700; color: var(--c-dark); font-size: 1.2rem; margin: 24px 0 12px; padding-left: 12px; border-left: 4px solid var(--c-primary);">{{ $typeName }} ({{ $items->count() }})</h2>

                @foreach($items as $bookmark)
                    @php $item = $bookmark->bookmarkable; @endphp
                    @if($item)
                    <div style="background: #fff; border: 1px solid #E5E7EB; border-radius: var(--r-base); padding: 16px 20px; margin-bottom: 10px; display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <strong style="color: var(--c-dark);">
                                @if($type === 'Modules\\Blog\\Models\\Article')
                                    {{ $item->title }}
                                @elseif($type === 'Modules\\Directory\\Models\\Tool')
                                    {{ $item->name }}
                                @elseif($type === 'Modules\\Dictionary\\Models\\Term')
                                    {{ $item->name }}
                                @elseif($type === 'Modules\\Acronyms\\Models\\Acronym')
                                    {{ $item->acronym }} — {{ $item->full_name }}
                                @else
                                    {{ $item->name ?? $item->title ?? '-' }}
                                @endif
                            </strong>
                            <p style="color: #6B7280; font-size: 13px; margin: 4px 0 0;">{{ __('Sauvegardé le') }} {{ $bookmark->created_at?->format('d/m/Y') }}</p>
                        </div>
                        <div>
                            @if($type === 'Modules\\Blog\\Models\\Article' && Route::has('blog.show'))
                                <a href="{{ route('blog.show', $item->slug) }}" style="color: var(--c-primary); font-weight: 600; font-size: 13px;">{{ __('Lire') }} →</a>
                            @elseif($type === 'Modules\\Directory\\Models\\Tool' && Route::has('directory.show'))
                                <a href="{{ route('directory.show', $item->slug) }}" style="color: var(--c-primary); font-weight: 600; font-size: 13px;">{{ __('Voir') }} →</a>
                            @elseif($type === 'Modules\\Dictionary\\Models\\Term' && Route::has('dictionary.show'))
                                <a href="{{ route('dictionary.show', $item->slug) }}" style="color: var(--c-primary); font-weight: 600; font-size: 13px;">{{ __('Lire') }} →</a>
                            @elseif($type === 'Modules\\Acronyms\\Models\\Acronym' && Route::has('acronyms.show'))
                                <a href="{{ route('acronyms.show', $item->getTranslation('slug', app()->getLocale())) }}" style="color: var(--c-primary); font-weight: 600; font-size: 13px;">{{ __('Voir') }} →</a>
                            @endif
                        </div>
                    </div>
                    @endif
                @endforeach
            @endforeach
        @endif
    </div>
</div>
@endsection
