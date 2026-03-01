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
<div class="card">
    <div class="card-header border-bottom py-3 px-4 d-flex flex-wrap gap-2 justify-content-between align-items-center">
        <h6 class="mb-0 d-flex align-items-center gap-2 flex-shrink-0">
            <i data-lucide="file-text"></i>
            {{ __('Comparaison révision') }} #{{ $revision->revision_number }}
        </h6>
        <a href="{{ route('admin.blog.articles.revisions', $article) }}" class="btn btn-sm btn-outline-secondary rounded-2 d-flex align-items-center gap-2 flex-shrink-0">
            <i data-lucide="arrow-left"></i>
            {{ __('Retour') }}
        </a>
    </div>
    <div class="card-body p-4">
        {{-- Side by side comparison --}}
        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <div class="card border border-primary border-opacity-25 h-100">
                    <div class="card-header bg-primary bg-opacity-10 py-2 px-3">
                        <h6 class="mb-0 text-primary d-flex align-items-center gap-2">
                            <i data-lucide="check-circle"></i>
                            {{ __('Version actuelle') }}
                        </h6>
                        <small class="text-muted">{{ $article->updated_at->format('d/m/Y H:i') }}</small>
                    </div>
                    <div class="card-body p-3">
                        <h6 class="mb-2">{{ $article->title }}</h6>
                        <div class="border rounded-2 p-2" style="max-height: 400px; overflow-y: auto;">
                            {!! $article->safe_content !!}
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card border border-warning border-opacity-25 h-100">
                    <div class="card-header bg-warning bg-opacity-10 py-2 px-3">
                        <h6 class="mb-0 text-warning d-flex align-items-center gap-2">
                            <i data-lucide="history"></i>
                            {{ __('Révision') }} #{{ $revision->revision_number }}
                        </h6>
                        <small class="text-muted">{{ $revision->created_at->format('d/m/Y H:i') }} {{ __('par') }} {{ $revision->user->name ?? __('Système') }}</small>
                    </div>
                    <div class="card-body p-3">
                        @php
                            $revTitle = json_decode($revision->title, true);
                            $revTitleText = is_array($revTitle) ? ($revTitle[app()->getLocale()] ?? reset($revTitle)) : $revision->title;
                            $revContent = json_decode($revision->content, true);
                            $revContentText = is_array($revContent) ? ($revContent[app()->getLocale()] ?? reset($revContent)) : $revision->content;
                        @endphp
                        <h6 class="mb-2">{{ $revTitleText }}</h6>
                        <div class="border rounded-2 p-2" style="max-height: 400px; overflow-y: auto;">
                            {!! $revContentText !!}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Diff view --}}
        <div class="card border border-info border-opacity-25">
            <div class="card-header bg-info bg-opacity-10 py-2 px-3">
                <h6 class="mb-0 text-info d-flex align-items-center gap-2">
                    <i data-lucide="git-compare"></i>
                    {{ __('Différences détectées') }}
                </h6>
            </div>
            <div class="card-body p-3">
                @if($diffTitle)
                <div class="mb-3">
                    <label class="form-label fw-semibold text-muted">{{ __('Titre') }}</label>
                    <div class="p-2 border rounded-2">{!! $diffTitle !!}</div>
                </div>
                @endif

                @if($diffContent)
                <div class="mb-0">
                    <label class="form-label fw-semibold text-muted">{{ __('Contenu') }}</label>
                    <div class="p-2 border rounded-2" style="max-height: 500px; overflow-y: auto;">{!! $diffContent !!}</div>
                </div>
                @endif

                @if(!$diffTitle && !$diffContent)
                <p class="text-muted mb-0">{{ __('Aucune différence de texte détectée.') }}</p>
                @endif
            </div>
        </div>
    </div>
    <div class="card-footer py-3 px-4 d-flex justify-content-between align-items-center">
        <a href="{{ route('admin.blog.articles.revisions', $article) }}" class="btn btn-outline-secondary rounded-2 d-flex align-items-center gap-2">
            <i data-lucide="arrow-left"></i>
            {{ __('Retour à l\'historique') }}
        </a>
        <form action="{{ route('admin.blog.articles.revisions.restore', [$article, $revision]) }}" method="POST">
            @csrf
            <button type="submit" class="btn btn-warning rounded-2 d-flex align-items-center gap-2" onclick="return confirm('{{ __('Restaurer cette version ?') }}')">
                <i data-lucide="refresh-cw"></i>
                {{ __('Restaurer cette révision') }}
            </button>
        </form>
    </div>
</div>
@endsection
