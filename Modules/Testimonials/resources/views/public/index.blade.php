@extends('fronttheme::themes.gosass.layouts.app')

@section('title', 'Témoignages - ' . config('app.name'))
@section('description', 'Découvrez les avis et témoignages de nos clients.')

@section('meta')
    {!! \Modules\SEO\Services\JsonLdService::render(
        ['@type' => 'WebPage', 'name' => 'Témoignages', 'url' => route('testimonials.show'), 'description' => 'Témoignages de nos clients'],
        \Modules\SEO\Services\JsonLdService::breadcrumbs([
            ['name' => 'Accueil', 'url' => url('/')],
            ['name' => 'Témoignages'],
        ])
    ) !!}
@endsection

@section('content')
<div class="cs_height_85 cs_height_lg_80"></div>
<div class="container text-center">
    <h2 class="cs_fs_50 cs_mb_15 wow fadeInDown">Témoignages de nos clients</h2>
    <p class="mb-0 wow fadeInUp">Découvrez ce que nos utilisateurs pensent de nous.</p>
</div>

<div class="cs_height_64 cs_height_lg_50"></div>
<div class="container">
    @if($testimonials->isEmpty())
        <div class="text-center py-5">
            <p class="cs_fs_21 text-muted">Aucun témoignage pour le moment.</p>
        </div>
    @else
        <div class="row cs_gap_y_30">
            @foreach($testimonials as $testimonial)
            <div class="col-lg-6 wow fadeIn" data-wow-delay="{{ $loop->index * 0.1 }}s">
                <div class="cs_card cs_style_1 cs_white_bg cs_radius_15 p-4 h-100 shadow-sm">
                    <div class="d-flex align-items-center mb-3">
                        @if($testimonial->author_avatar)
                            <img src="{{ $testimonial->author_avatar }}" alt="{{ $testimonial->author_name }}" class="rounded-circle me-3" width="56" height="56" style="object-fit:cover">
                        @else
                            <div class="rounded-circle me-3 d-flex align-items-center justify-content-center" style="width:56px;height:56px;background:#6366f1;color:#fff;font-size:1.2rem;font-weight:600">
                                {{ mb_substr($testimonial->author_name, 0, 1) }}
                            </div>
                        @endif
                        <div>
                            <h5 class="cs_fs_18 cs_heading_color mb-0">{{ $testimonial->author_name }}</h5>
                            @if($testimonial->author_title)
                                <small class="text-muted">{{ $testimonial->author_title }}</small>
                            @endif
                        </div>
                    </div>
                    <div class="text-warning mb-2">
                        @for($i = 1; $i <= 5; $i++)
                            <span>{{ $i <= $testimonial->rating ? '★' : '☆' }}</span>
                        @endfor
                    </div>
                    <blockquote class="cs_fs_16 mb-0" style="font-style:italic">
                        « {!! $testimonial->safeContent() !!} »
                    </blockquote>
                </div>
            </div>
            @endforeach
        </div>
    @endif
</div>
<div class="cs_height_100 cs_height_lg_80"></div>
@endsection
