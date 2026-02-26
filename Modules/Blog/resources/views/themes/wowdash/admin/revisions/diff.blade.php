@extends('backoffice::layouts.admin', ['title' => __('Comparaison'), 'subtitle' => $article->title])

@push('styles')
<style>
    ins.diff-added {
        background-color: rgba(40, 167, 69, 0.25);
        color: #5cb85c;
        text-decoration: none;
        padding: 1px 4px;
        border-radius: 3px;
        font-weight: 600;
    }
    del.diff-removed {
        background-color: rgba(220, 53, 69, 0.25);
        color: #e57373;
        text-decoration: line-through;
        padding: 1px 4px;
        border-radius: 3px;
    }
</style>
@endpush

@section('content')
<div class="card radius-12">
    <div class="card-header border-bottom bg-base py-16 px-24 d-flex flex-wrap gap-2 justify-content-between align-items-center">
        <h6 class="mb-0 d-flex align-items-center gap-2 flex-shrink-0">
            <iconify-icon icon="solar:document-text-bold" class="icon text-xl"></iconify-icon>
            {{ __('Comparaison révision') }} #{{ $revision->revision_number }}
        </h6>
        <a href="{{ route('admin.blog.articles.revisions', $article) }}" class="btn btn-sm btn-outline-secondary-600 radius-8 d-flex align-items-center gap-2 flex-shrink-0">
            <iconify-icon icon="solar:arrow-left-outline" class="icon"></iconify-icon>
            {{ __('Retour') }}
        </a>
    </div>
    <div class="card-body p-24">
        {{-- Side by side comparison --}}
        <div class="row g-3 mb-24">
            <div class="col-md-6">
                <div class="card border border-primary-200 h-100">
                    <div class="card-header bg-primary-50 py-12 px-16">
                        <h6 class="mb-0 text-primary-600 d-flex align-items-center gap-2">
                            <iconify-icon icon="solar:check-circle-bold" class="icon"></iconify-icon>
                            {{ __('Version actuelle') }}
                        </h6>
                        <small class="text-secondary-light">{{ $article->updated_at->format('d/m/Y H:i') }}</small>
                    </div>
                    <div class="card-body p-16">
                        <h6 class="mb-8">{{ $article->title }}</h6>
                        <div class="border radius-8 p-12 bg-base" style="max-height: 400px; overflow-y: auto;">
                            {!! $article->content !!}
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card border border-warning-200 h-100">
                    <div class="card-header bg-warning-50 py-12 px-16">
                        <h6 class="mb-0 text-warning-600 d-flex align-items-center gap-2">
                            <iconify-icon icon="solar:history-bold" class="icon"></iconify-icon>
                            {{ __('Révision') }} #{{ $revision->revision_number }}
                        </h6>
                        <small class="text-secondary-light">{{ $revision->created_at->format('d/m/Y H:i') }} {{ __('par') }} {{ $revision->user->name ?? __('Système') }}</small>
                    </div>
                    <div class="card-body p-16">
                        @php
                            $revTitle = json_decode($revision->title, true);
                            $revTitleText = is_array($revTitle) ? ($revTitle[app()->getLocale()] ?? reset($revTitle)) : $revision->title;
                            $revContent = json_decode($revision->content, true);
                            $revContentText = is_array($revContent) ? ($revContent[app()->getLocale()] ?? reset($revContent)) : $revision->content;
                        @endphp
                        <h6 class="mb-8">{{ $revTitleText }}</h6>
                        <div class="border radius-8 p-12 bg-base" style="max-height: 400px; overflow-y: auto;">
                            {!! $revContentText !!}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Diff view --}}
        <div class="card border border-info-200">
            <div class="card-header bg-info-50 py-12 px-16">
                <h6 class="mb-0 text-info-600 d-flex align-items-center gap-2">
                    <iconify-icon icon="solar:sort-horizontal-bold" class="icon"></iconify-icon>
                    {{ __('Différences détectées') }}
                </h6>
            </div>
            <div class="card-body p-16">
                @if($diffTitle)
                <div class="mb-16">
                    <label class="form-label fw-semibold text-secondary-light">{{ __('Titre') }}</label>
                    <div class="p-12 border radius-8 bg-base">{!! $diffTitle !!}</div>
                </div>
                @endif

                @if($diffContent)
                <div class="mb-0">
                    <label class="form-label fw-semibold text-secondary-light">{{ __('Contenu') }}</label>
                    <div class="p-12 border radius-8 bg-base" style="max-height: 500px; overflow-y: auto;">{!! $diffContent !!}</div>
                </div>
                @endif

                @if(!$diffTitle && !$diffContent)
                <p class="text-secondary-light mb-0">{{ __('Aucune différence de texte détectée.') }}</p>
                @endif
            </div>
        </div>
    </div>
    <div class="card-footer bg-base py-16 px-24 d-flex justify-content-between align-items-center">
        <a href="{{ route('admin.blog.articles.revisions', $article) }}" class="btn btn-outline-secondary-600 radius-8 d-flex align-items-center gap-2">
            <iconify-icon icon="solar:arrow-left-outline" class="icon"></iconify-icon>
            {{ __('Retour à l\'historique') }}
        </a>
        <form action="{{ route('admin.blog.articles.revisions.restore', [$article, $revision]) }}" method="POST">
            @csrf
            <button type="submit" class="btn btn-warning-600 radius-8 d-flex align-items-center gap-2" onclick="return confirm('{{ __('Restaurer cette version ?') }}')">
                <iconify-icon icon="solar:restart-outline" class="icon"></iconify-icon>
                {{ __('Restaurer cette révision') }}
            </button>
        </form>
    </div>
</div>
@endsection
