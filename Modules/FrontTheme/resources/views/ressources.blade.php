{{-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca --}}
@extends(fronttheme_layout())

@section('title', __('Ressources') . ' - ' . config('app.name'))
@section('meta_description', __('Explorez toutes les ressources disponibles sur laveille.ai : répertoire techno, glossaire IA, acronymes, blog, actualités et outils gratuits.'))

@section('breadcrumb')
    @include('fronttheme::partials.breadcrumb', ['breadcrumbTitle' => __('Ressources')])
@endsection

@section('content')
<h1 class="sr-only">{{ __('Ressources') }} — {{ config('app.name') }}</h1>
<section class="wpo-blog-single-section section-padding">
    <div class="container">
        <div class="row">
            <div class="col col-lg-10 offset-lg-1">
                <h2 style="font-family: var(--f-heading, inherit); font-weight: 700; margin: 0 0 8px;">{{ __('Ressources') }}</h2>
                <p style="color: #6B7280; margin-bottom: 24px;">{{ __('Explorez toutes les ressources disponibles sur laveille.ai.') }}</p>

                <div class="row" id="resource-cards">
                    @foreach($sections as $section)
                    <div class="col-md-4 col-sm-6" style="margin-bottom: 20px;">
                        <a href="{{ $section['url'] }}" style="text-decoration: none; color: inherit; display: block;"
                           class="resource-card"
                           data-title="{{ $section['title'] }}"
                           data-description="{{ $section['description'] }}"
                           data-url="{{ $section['url'] }}"
                           data-count="{{ $section['count'] ?? '' }}">
                            <div class="panel panel-default" style="border-radius: 12px; transition: box-shadow .2s; margin-bottom: 0; height: 100%;">
                                <div class="panel-body" style="padding: 20px;">
                                    <div style="font-size: 2.5rem; line-height: 1; margin-bottom: 10px;">{{ $section['icon'] }}</div>
                                    <h4 style="font-weight: 700; margin: 0 0 6px; color: var(--c-dark, #1a1a2e);">{{ $section['title'] }}</h4>
                                    <p style="color: #6B7280; font-size: 13px; margin: 0 0 12px; line-height: 1.5;">{{ $section['description'] }}</p>
                                    <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                                        @if($section['count'])
                                            <span style="background: var(--c-primary, #0B7285); color: #fff; padding: 3px 10px; border-radius: 12px; font-size: 12px; font-weight: 600;">{{ $section['count'] }} {{ __('entrées') }}</span>
                                        @endif
                                        @if($section['updated_at'])
                                            <span style="color: #9CA3AF; font-size: 11px;">{{ __('Mis à jour') }} {{ \Carbon\Carbon::parse($section['updated_at'])->diffForHumans() }}</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>
                    @endforeach
                </div>

                {{-- Boutons copier --}}
                <div x-data="{ feedback: '', show: false }" style="text-align: center; margin-top: 24px;">
                    <a href="javascript:void(0)"
                       @click="
                           let md = '# Ressources — {{ config('app.name') }}\n\n';
                           document.querySelectorAll('.resource-card').forEach(c => {
                               let t = c.dataset.title;
                               let d = c.dataset.description;
                               let u = c.dataset.url;
                               let n = c.dataset.count;
                               md += '## ' + t + (n ? ' (' + n + ' entrées)' : '') + '\n' + d + '\n' + u + '\n\n';
                           });
                           navigator.clipboard.writeText(md).then(() => { feedback = '{{ __('Markdown copié!') }}'; show = true; setTimeout(() => show = false, 2000); });
                       "
                       style="display: inline-block; background: var(--c-primary, #0B7285); color: #fff; padding: 10px 24px; border-radius: 8px; font-weight: 600; font-size: 14px; text-decoration: none; cursor: pointer;">
                        {{ __('Copier en Markdown') }}
                    </a>
                    <a href="javascript:void(0)"
                       @click="
                           let txt = 'Ressources — {{ config('app.name') }}\n\n';
                           document.querySelectorAll('.resource-card').forEach(c => {
                               let t = c.dataset.title;
                               let d = c.dataset.description;
                               let u = c.dataset.url;
                               let n = c.dataset.count;
                               txt += t + (n ? ' (' + n + ' entrées)' : '') + ' - ' + d + ' - ' + u + '\n';
                           });
                           navigator.clipboard.writeText(txt).then(() => { feedback = '{{ __('Texte copié!') }}'; show = true; setTimeout(() => show = false, 2000); });
                       "
                       style="display: inline-block; background: #fff; color: var(--c-dark, #1a1a2e); border: 1px solid #D1D5DB; padding: 10px 24px; border-radius: 8px; font-weight: 600; font-size: 14px; text-decoration: none; cursor: pointer; margin-left: 8px;">
                        {{ __('Copier en texte brut') }}
                    </a>
                    <div x-show="show" x-transition style="margin-top: 12px; color: var(--c-primary, #0B7285); font-weight: 600;" x-text="feedback"></div>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
.resource-card .panel:hover { box-shadow: 0 4px 16px rgba(0,0,0,.1); }
</style>
@endsection
