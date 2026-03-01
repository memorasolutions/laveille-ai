@extends('backoffice::layouts.admin', ['title' => __('Révision').' #'.$revision->revision_number, 'subtitle' => $article->title])

@section('content')
<div class="card">
    <div class="card-header border-bottom py-3 px-4">
        <h6 class="mb-1 d-flex align-items-center gap-2">
            <i data-lucide="history"></i>
            {{ __('Révision') }} #{{ $revision->revision_number }}
        </h6>
        <p class="text-muted text-sm mb-0">
            {{ $revision->created_at->format('d/m/Y H:i') }} {{ __('par') }} {{ $revision->user->name ?? __('Système') }}
        </p>
    </div>
    <div class="card-body p-4">
        <div class="mb-3">
            <label class="form-label fw-semibold text-muted">{{ __('Titre') }}</label>
            <p class="fw-medium">{{ $revision->title }}</p>
        </div>

        @if($revision->excerpt)
        <div class="mb-3">
            <label class="form-label fw-semibold text-muted">{{ __('Extrait') }}</label>
            <p class="text-muted">{{ $revision->excerpt }}</p>
        </div>
        @endif

        <div class="mb-3">
            <label class="form-label fw-semibold text-muted">{{ __('Contenu') }}</label>
            <div class="border rounded-2 p-3">{!! $revision->content !!}</div>
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
