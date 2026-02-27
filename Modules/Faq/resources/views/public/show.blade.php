@extends('fronttheme::themes.gosass.layouts.app')

@section('title', __('FAQ').' - '.config('app.name'))
@section('description', __('Questions fréquentes sur notre plateforme SaaS. Trouvez rapidement les réponses à vos questions.'))

@section('content')

@if(!empty($jsonLdString))
<script type="application/ld+json">{!! $jsonLdString !!}</script>
@endif

<div class="cs_height_85 cs_height_lg_80"></div>
<div class="container text-center">
    <h2 class="cs_fs_50 cs_mb_15 wow fadeInDown">{{ __('Questions fréquentes') }}</h2>
    <p class="mb-0 wow fadeInUp">{{ __('Trouvez les réponses aux questions les plus courantes sur notre plateforme.') }}</p>
</div>

@if($categories->count() > 1)
<div class="cs_height_30 cs_height_lg_20"></div>
<div class="container text-center">
    <div class="d-flex flex-wrap justify-content-center gap-2">
        <button class="cs_btn cs_style_1 cs_accent_bg cs_white_color cs_radius_30 cs_fs_14 cs_semibold faq-filter active" data-category="all">
            <span>{{ __('Toutes') }}</span>
        </button>
        @foreach($categories as $cat)
        <button class="cs_btn cs_style_1 cs_white_bg cs_heading_color cs_radius_30 cs_fs_14 faq-filter" data-category="{{ $cat ?? 'general' }}">
            <span>{{ $cat ?? __('Général') }}</span>
        </button>
        @endforeach
    </div>
</div>
@endif

<div class="cs_height_64 cs_height_lg_50"></div>
<div class="container">
    <div class="row cs_gap_y_24 position-relative z-1">
        @foreach($faqs as $category => $items)
            @foreach($items as $faq)
            <div class="col-xl-6 wow fadeIn faq-item" data-category="{{ $category ?? 'general' }}" data-wow-delay="{{ $loop->index * 0.05 }}s">
                <div class="cs_accordian cs_radius_15 cs_white_bg cs_type_2 position-relative">
                    <div class="cs_accordian_head cs_fs_21 cs_heading_color">
                        {{ $faq->question }}
                        <span class="cs_accordian_toggle position-absolute"></span>
                    </div>
                    <div class="cs_accordian_body">
                        {!! $faq->safeAnswer() !!}
                    </div>
                </div>
            </div>
            @endforeach
        @endforeach
    </div>

    <div class="cs_height_60 cs_height_lg_40"></div>
    <div class="text-center">
        <p class="cs_fs_18 mb-3">{{ __('Vous ne trouvez pas la réponse que vous cherchez ?') }}</p>
        @if(Route::has('contact.show'))
        <a href="{{ route('contact.show') }}" class="cs_btn cs_style_1 cs_accent_bg cs_purple_hover cs_fs_16 cs_semibold cs_white_color cs_radius_30">
            <span>{{ __('Contactez-nous') }}</span>
            <span class="cs_btn_icon cs_center overflow-hidden"><i class="fa-solid fa-arrow-right"></i></span>
        </a>
        @endif
    </div>
</div>
<div class="cs_height_140 cs_height_lg_80"></div>

@endsection

@push('custom-scripts')
@if($categories->count() > 1)
<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.faq-filter').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.faq-filter').forEach(b => {
                b.classList.remove('cs_accent_bg', 'cs_white_color', 'active');
                b.classList.add('cs_white_bg', 'cs_heading_color');
            });
            this.classList.add('cs_accent_bg', 'cs_white_color', 'active');
            this.classList.remove('cs_white_bg', 'cs_heading_color');

            const cat = this.dataset.category;
            document.querySelectorAll('.faq-item').forEach(item => {
                item.style.display = (cat === 'all' || item.dataset.category === cat) ? '' : 'none';
            });
        });
    });
});
</script>
@endif
@endpush
