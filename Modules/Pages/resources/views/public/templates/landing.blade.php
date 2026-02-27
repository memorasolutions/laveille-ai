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
    <div class="text-center py-5">
        <h1 class="cs_fs_50 cs_heading_color cs_mb_30">{{ $page->title }}</h1>
        @if($page->excerpt)
        <p class="cs_fs_21 mx-auto" style="max-width: 700px; color: #6c757d;">{{ $page->excerpt }}</p>
        @endif
    </div>
</div>
<div class="cs_height_40 cs_height_lg_30"></div>
<div class="container">
    <div class="cs_page_content cs_fs_18" style="line-height:1.8">
        {!! render_shortcodes($page->safe_content) !!}
    </div>
</div>
<div class="cs_height_100 cs_height_lg_80"></div>
@endsection
