<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::themes.backend.layouts.admin', ['title' => __('Comparaison'), 'subtitle' => $article->title])

@push('plugin-styles')
<style>
    ins.diff-added {
        background-color: rgba(34, 197, 94, 0.2);
        color: #16a34a;
        text-decoration: none;
        padding: 1px 4px;
        border-radius: 3px;
        font-weight: 600;
    }
    del.diff-removed {
        background-color: rgba(239, 68, 68, 0.2);
        color: #dc2626;
        text-decoration: line-through;
        padding: 1px 4px;
        border-radius: 3px;
    }
</style>
@endpush

@section('content')

<div class="card">
    <div class="card-header py-3 px-4 border-bottom">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
            <h4 class="fw-bold mb-0 d-flex align-items-center gap-2">
                <i data-lucide="git-compare" class="flex-shrink-0"></i>
                {{ __('Comparaison révision') }} #{{ $revision->revision_number }}
            </h4>
            <a href="{{ route('admin.blog.articles.revisions', $article) }}"
               class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-2 flex-shrink-0">
                <i data-lucide="arrow-left" class="icon-sm"></i>
                {{ __('Retour') }}
            </a>
        </div>
    </div>
    <div class="card-body p-4">
        {{-- Comparaison côte à côte --}}
        <div class="row g-3 mb-3">
            <div class="col-md-6">
                <div class="card border-success">
                    <div class="card-header py-3 px-4 border-bottom border-success bg-success bg-opacity-10">
                        <h5 class="fw-semibold text-success d-flex align-items-center gap-2 small mb-0">
                            <i data-lucide="check-circle" class="icon-sm"></i>
                            {{ __('Version actuelle') }}
                        </h5>
                        <p class="small text-muted mt-1 mb-0">{{ $article->updated_at->format('d/m/Y H:i') }}</p>
                    </div>
                    <div class="card-body p-4">
                        <h6 class="fw-semibold text-body mb-3 small">{{ $article->title }}</h6>
                        <div class="border rounded-3 p-3 overflow-auto small" style="max-height:24rem;">
                            {!! $article->safe_content !!}
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card border-warning">
                    <div class="card-header py-3 px-4 border-bottom border-warning bg-warning bg-opacity-10">
                        <h5 class="fw-semibold text-warning d-flex align-items-center gap-2 small mb-0">
                            <i data-lucide="history" class="icon-sm"></i>
                            {{ __('Révision') }} #{{ $revision->revision_number }}
                        </h5>
                        <p class="small text-muted mt-1 mb-0">{{ $revision->created_at->format('d/m/Y H:i') }} {{ __('par') }} {{ $revision->user->name ?? __('Système') }}</p>
                    </div>
                    <div class="card-body p-4">
                        @php
                            $revTitle = json_decode($revision->title, true);
                            $revTitleText = is_array($revTitle) ? ($revTitle[app()->getLocale()] ?? reset($revTitle)) : $revision->title;
                            $revContent = json_decode($revision->content, true);
                            $revContentText = is_array($revContent) ? ($revContent[app()->getLocale()] ?? reset($revContent)) : $revision->content;
                        @endphp
                        <h6 class="fw-semibold text-body mb-3 small">{{ $revTitleText }}</h6>
                        <div class="border rounded-3 p-3 overflow-auto small" style="max-height:24rem;">
                            {!! $revContentText !!}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Vue différences --}}
        <div class="card border-info">
            <div class="card-header py-3 px-4 border-bottom border-info bg-info bg-opacity-10">
                <h5 class="fw-semibold text-info d-flex align-items-center gap-2 small mb-0">
                    <i data-lucide="arrow-left-right" class="icon-sm"></i>
                    {{ __('Différences détectées') }}
                </h5>
            </div>
            <div class="card-body p-4">
                @if($diffTitle)
                <div class="mb-3">
                    <label class="form-label small fw-semibold text-muted text-uppercase mb-2">{{ __('Titre') }}</label>
                    <div class="border rounded-3 p-3 small">{!! $diffTitle !!}</div>
                </div>
                @endif

                @if($diffContent)
                <div class="mb-3">
                    <label class="form-label small fw-semibold text-muted text-uppercase mb-2">{{ __('Contenu') }}</label>
                    <div class="border rounded-3 p-3 overflow-auto small" style="max-height:500px;">{!! $diffContent !!}</div>
                </div>
                @endif

                @if(!$diffTitle && !$diffContent)
                <p class="text-muted small mb-0">{{ __('Aucune différence de texte détectée.') }}</p>
                @endif
            </div>
        </div>
    </div>
    <div class="card-footer py-3 px-4 d-flex align-items-center justify-content-between">
        <a href="{{ route('admin.blog.articles.revisions', $article) }}"
           class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-2">
            <i data-lucide="arrow-left" class="icon-sm"></i>
            {{ __('Retour à l\'historique') }}
        </a>
        <form action="{{ route('admin.blog.articles.revisions.restore', [$article, $revision]) }}" method="POST" x-data>
            @csrf
            <button type="button"
                    class="btn btn-sm btn-outline-warning d-inline-flex align-items-center gap-2"
                    @click="$dispatch('confirm-action', { title: @js(__('Confirmer')), message: @js(__('Restaurer cette version ?')), action: () => $el.closest('form').submit() })">
                <i data-lucide="undo-2" class="icon-sm"></i>
                {{ __('Restaurer cette révision') }}
            </button>
        </form>
    </div>
</div>

@endsection
