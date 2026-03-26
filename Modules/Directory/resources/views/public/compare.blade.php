{{-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca --}}
@extends(fronttheme_layout())

@section('title', 'Comparatif : ' . $category->name . ' - ' . config('app.name'))
@section('meta_description', 'Comparez les meilleurs outils ' . $category->name . '. Tarifs, fonctionnalites et avis.')

@section('breadcrumb')
    @include('fronttheme::partials.breadcrumb', ['breadcrumbTitle' => 'Comparatif : ' . $category->name])
@endsection

@push('styles')
<style>
    .cmp-wrap { overflow-x: auto; -webkit-overflow-scrolling: touch; }
    .cmp-table { width: 100%; border-collapse: separate; border-spacing: 0; background: #fff; border-radius: var(--r-base); box-shadow: 0 2px 8px rgba(0,0,0,0.06); margin-bottom: 30px; }
    .cmp-table th, .cmp-table td { padding: 12px 15px; vertical-align: middle; border-top: 1px solid #eee; text-align: left; }
    .cmp-table thead th { font-weight: 700; background: #f8f9fa; border-top: none; border-bottom: 2px solid #e5e7eb; color: var(--c-dark); font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px; }
    .cmp-table tbody tr:hover { background: #f0f9fa; }
    .cmp-table .tool-thumb { width: 50px; height: 35px; object-fit: cover; border-radius: 4px; margin-right: 10px; vertical-align: middle; }
    .cmp-table .tool-name { font-weight: 700; color: var(--c-dark); text-decoration: none; }
    .cmp-table .tool-name:hover { color: var(--c-primary); }
    .cmp-table .tool-desc { color: #6b7280; font-size: 13px; line-height: 1.4; }
    .cmp-btn { background: var(--c-primary); color: #fff !important; border: none; padding: 6px 14px; border-radius: var(--r-btn); font-size: 12px; font-weight: 600; text-decoration: none !important; transition: opacity 0.2s; }
    .cmp-btn:hover { opacity: 0.85; color: #fff; }

    .cmp-tabs { display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 24px; }
    .cmp-tab { display: inline-block; padding: 7px 16px; border-radius: var(--r-btn); background: #f3f4f6; color: var(--c-dark); font-weight: 600; font-size: 13px; text-decoration: none !important; transition: all 0.2s; }
    .cmp-tab:hover { background: #e5e7eb; color: var(--c-dark); }
    .cmp-tab.active { background: var(--c-primary); color: #fff !important; }

    .cmp-header { margin-bottom: 24px; }
    .cmp-header h1 { font-family: var(--f-heading); font-weight: 800; color: var(--c-dark); margin: 0 0 6px; font-size: 1.8rem; }
    .cmp-header p { color: #6b7280; margin: 0; }

    .cmp-back { display: inline-flex; align-items: center; gap: 6px; color: var(--c-primary); font-weight: 600; font-size: 14px; text-decoration: none !important; margin-bottom: 20px; }
    .cmp-back:hover { color: var(--c-dark); }

    @media (max-width: 768px) {
        .cmp-table td:nth-child(5) { min-width: 200px; }
    }
</style>
@endpush

@section('content')
<div class="container" style="padding-top: 30px; padding-bottom: 40px;">

    <a href="{{ route('directory.index') }}" class="cmp-back"><i class="ti-arrow-left"></i> {{ __('Retour au repertoire') }}</a>

    <div class="cmp-header">
        <h1>{{ $category->icon ?? '' }} Comparatif : {{ $category->name }}</h1>
        <p>{{ $tools->count() }} {{ __('outils compares') }}</p>
    </div>

    <div class="cmp-tabs">
        @foreach($allCategories as $cat)
            <a href="{{ route('directory.compare', $cat->slug) }}"
               class="cmp-tab {{ $category->slug === $cat->slug ? 'active' : '' }}">
                {{ $cat->icon ?? '' }} {{ $cat->name }}
            </a>
        @endforeach
    </div>

    @if($tools->isEmpty())
        <div style="text-align: center; padding: 60px 20px; background: #f9fafb; border-radius: var(--r-base);">
            <p style="color: #6b7280;">{{ __('Aucun outil publie dans cette categorie.') }}</p>
        </div>
    @else
    <div class="cmp-wrap">
        <table class="cmp-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>{{ __('Outil') }}</th>
                    <th>{{ __('Tarification') }}</th>
                    <th>{{ __('Annee') }}</th>
                    <th>{{ __('Description') }}</th>
                    <th>{{ __('Fiche') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($tools as $index => $tool)
                <tr>
                    <td style="color: #9ca3af; font-weight: 700;">{{ $index + 1 }}</td>
                    <td>
                        @php
                            $screenshotSrc = $tool->screenshot
                                ? (str_starts_with($tool->screenshot, 'http') ? $tool->screenshot : asset($tool->screenshot))
                                : '';
                        @endphp
                        @if($screenshotSrc)
                            <img src="{{ $screenshotSrc }}" alt="{{ $tool->name }}" class="tool-thumb" loading="lazy">
                        @endif
                        <a href="{{ route('directory.show', $tool->slug) }}" class="tool-name">{{ $tool->name }}</a>
                    </td>
                    <td>
                        <span class="rt-badge badge-{{ $tool->pricing }}">{{ $pricingLabels[$tool->pricing] ?? ucfirst($tool->pricing) }}</span>
                    </td>
                    <td>{{ $tool->launch_year ?? '-' }}</td>
                    <td class="tool-desc">{{ Str::limit($tool->short_description, 150) }}</td>
                    <td>
                        <a href="{{ route('directory.show', $tool->slug) }}" class="cmp-btn">Voir <i class="ti-arrow-right"></i></a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>
@endsection
