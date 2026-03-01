<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::themes.backend.layouts.admin', ['title' => __('Révision').' #'.$revision->revision_number, 'subtitle' => $article->title])

@section('content')

<div class="card">
    <div class="card-header py-3 px-4 border-bottom">
        <div class="d-flex align-items-center gap-2 mb-1">
            <i data-lucide="history" class="text-primary"></i>
            <h4 class="fw-bold mb-0">{{ __('Révision') }} #{{ $revision->revision_number }}</h4>
        </div>
        <p class="small text-muted mb-0">
            {{ $revision->created_at->format('d/m/Y H:i') }} {{ __('par') }} {{ $revision->user->name ?? __('Système') }}
        </p>
    </div>
    <div class="card-body p-4">
        <div class="mb-3">
            <label class="form-label small fw-semibold text-muted text-uppercase mb-2">{{ __('Titre') }}</label>
            <p class="fw-medium text-body">{{ $revision->title }}</p>
        </div>

        @if($revision->excerpt)
        <div class="mb-3">
            <label class="form-label small fw-semibold text-muted text-uppercase mb-2">{{ __('Extrait') }}</label>
            <p class="text-muted small">{{ $revision->excerpt }}</p>
        </div>
        @endif

        <div>
            <label class="form-label small fw-semibold text-muted text-uppercase mb-2">{{ __('Contenu') }}</label>
            <div class="border rounded-3 p-4">{!! $revision->content !!}</div>
        </div>
    </div>
    <div class="card-footer py-3 px-4 d-flex align-items-center justify-content-between">
        <a href="{{ route('admin.blog.articles.revisions', $article) }}"
           class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-2">
            <i data-lucide="arrow-left" class="icon-sm"></i>
            {{ __('Retour à l\'historique') }}
        </a>
        <form action="{{ route('admin.blog.articles.revisions.restore', [$article, $revision]) }}" method="POST">
            @csrf
            <button type="submit"
                    class="btn btn-sm btn-outline-warning d-inline-flex align-items-center gap-2"
                    onclick="return confirm('{{ __('Restaurer cette version ?') }}')">
                <i data-lucide="undo-2" class="icon-sm"></i>
                {{ __('Restaurer cette révision') }}
            </button>
        </form>
    </div>
</div>

@endsection
