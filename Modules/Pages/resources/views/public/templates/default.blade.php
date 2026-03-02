<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
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
@if(!empty($isPreview))
<div style="position:fixed;top:0;left:0;right:0;z-index:9999;background:#f59e0b;color:#000;text-align:center;padding:10px 16px;font-weight:600;font-size:14px;">
    Apercu - Ce contenu n'est pas encore publie
    <a href="{{ route('admin.pages.edit', $page) }}" style="color:#000;text-decoration:underline;margin-left:16px;">Retour a l'edition</a>
</div>
<div style="height:44px;"></div>
@endif
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
<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            @if($page->excerpt)
            <p class="cs_fs_21 cs_heading_color mb-4">{{ $page->excerpt }}</p>
            @endif
            <h1 class="cs_fs_50 cs_mb_30">{{ $page->title }}</h1>
            <div class="cs_page_content cs_fs_18" style="line-height:1.8">
                {!! render_shortcodes($page->safe_content) !!}
            </div>
        </div>
    </div>
</div>
<div class="cs_height_100 cs_height_lg_80"></div>
@endsection
