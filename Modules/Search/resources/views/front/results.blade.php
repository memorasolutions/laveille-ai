<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends(fronttheme_layout())

@section('title', __('Recherche') . ' : ' . $query . ' - ' . config('app.name'))
@section('meta_description', __('Résultats de recherche pour') . ' « ' . $query . ' » - ' . config('app.name'))

@section('breadcrumb')
    @include('fronttheme::partials.breadcrumb', [
        'breadcrumbTitle' => __('Recherche'),
        'breadcrumbItems' => [__('Recherche'), $query],
    ])
@endsection

@section('content')
    <section class="wpo-blog-single-section section-padding">
        <div class="container">
            <div class="row">
                <div class="col col-lg-8 col-12">
                    <div class="wpo-blog-content">
                        <h1>{{ __('Résultats pour') }} « {{ $query }} »</h1>
                        <p style="color:#777;margin-bottom:20px;">{{ $results['total'] }} {{ __('résultat(s) trouvé(s)') }}</p>

                        @if ($results['total'] === 0)
                            <div class="alert alert-info">{{ __('Aucun résultat trouvé pour votre recherche. Essayez avec des mots-clés différents ou vérifiez l\'orthographe.') }}</div>
                        @else
                            <div x-data="{ tab: 'all' }" class="search-tabs-wrapper">
                                <div class="nw-chips" style="margin-bottom:20px;">
                                    <button type="button" class="nw-chip" :class="tab === 'all' ? 'active' : ''" @click="tab = 'all'" role="tab" :aria-selected="tab === 'all'">{{ __('Toutes') }} ({{ $results['total'] }})</button>
                                    @foreach ($results['sections'] as $sec)
                                        <button type="button" class="nw-chip" :class="tab === '{{ $sec['key'] }}' ? 'active' : ''" @click="tab = '{{ $sec['key'] }}'" role="tab" :aria-selected="tab === '{{ $sec['key'] }}'">{!! $sec['icon'] !!} {{ $sec['label'] }} ({{ $sec['count'] }})</button>
                                    @endforeach
                                </div>

                                @foreach ($results['sections'] as $sec)
                                    <div x-show="tab === 'all' || tab === '{{ $sec['key'] }}'" x-transition>
                                        <h2 style="margin-top:30px;font-size:22px;">{!! $sec['icon'] !!} {{ $sec['label'] }} <span class="badge" style="background:var(--c-primary);color:#fff;margin-left:8px;font-size:13px;">{{ $sec['count'] }}</span></h2>
                                        @foreach ($sec['paginator'] as $item)
                                            <div class="media" style="margin-bottom:16px;padding-bottom:16px;{{ !$loop->last ? 'border-bottom:1px solid #f0f0f0;' : '' }}">
                                                <div class="media-body">
                                                    <h4 style="margin:0 0 4px;font-size:15px;">
                                                        <a href="{{ $item->searchableResultUrl() }}">{{ $item->searchableResultTitle() }}</a>
                                                    </h4>
                                                    <p style="color:#777;font-size:13px;margin:0;">{{ $item->searchableResultExcerpt() }}</p>
                                                </div>
                                            </div>
                                        @endforeach
                                        <div class="text-center" style="margin-top:15px;">
                                            {{ $sec['paginator']->appends(['q' => $query])->links() }}
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
                <div class="col col-lg-4 col-12">
                    @include('fronttheme::partials.sidebar')
                </div>
            </div>
        </div>
    </section>
@endsection

@push('styles')
<style>
.nw-chip { cursor: pointer; }
.nw-chip.active { background: var(--c-primary, #0B7285); color: #fff; border-color: var(--c-primary); }
</style>
@endpush
