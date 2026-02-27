@extends('fronttheme::themes.gosass.layouts.app')

@section('title', $page->meta_title ?? $page->title)

@section('meta')
    @if($page->meta_description)
        <meta name="description" content="{{ $page->meta_description }}">
    @endif
    {!! \Modules\SEO\Services\JsonLdService::render(
        \Modules\SEO\Services\JsonLdService::webPage($page),
        \Modules\SEO\Services\JsonLdService::breadcrumbs([
            ['name' => 'Accueil', 'url' => url('/')],
            ['name' => $page->title],
        ])
    ) !!}
@endsection

@section('content')
<div class="cs_height_58 cs_height_lg_40"></div>
<div class="container">
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ url('/') }}" class="cs_accent_color">Accueil</a></li>
            <li class="breadcrumb-item active" aria-current="page">{{ $page->title }}</li>
        </ol>
    </nav>
</div>
<div class="cs_height_40 cs_height_lg_30"></div>
<div class="container-fluid px-lg-5">
    @if($page->excerpt)
    <p class="cs_fs_21 cs_heading_color mb-4 text-center">{{ $page->excerpt }}</p>
    @endif
    <h1 class="cs_fs_50 cs_mb_30 text-center">{{ $page->title }}</h1>
    <div class="cs_page_content cs_fs_18" style="line-height:1.8">
        {!! render_shortcodes($page->safe_content) !!}
    </div>
</div>
<div class="cs_height_100 cs_height_lg_80"></div>
@endsection
