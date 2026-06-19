<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@props(['updated', 'published' => null, 'title' => null])
<p style="font-size:.9rem; color: var(--sys-text-muted, #5B6470); margin:8px 0 24px;">
    <i class="fi flaticon-calendar"></i> Dernière mise à jour : {{ \Illuminate\Support\Carbon::parse($updated)->locale('fr_CA')->translatedFormat('d F Y') }}
</p>
@php
    $pf = ['@context'=>'https://schema.org','@type'=>'WebPage','url'=>request()->url(),'inLanguage'=>'fr-CA','dateModified'=>\Illuminate\Support\Carbon::parse($updated)->toIso8601String(),'isPartOf'=>['@type'=>'WebSite','name'=>config('app.name'),'url'=>config('app.url')]];
    if($title){ $pf['name']=$title; }
    if($published){ $pf['datePublished']=\Illuminate\Support\Carbon::parse($published)->toIso8601String(); }
@endphp
<script type="application/ld+json">{!! json_encode($pf, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
