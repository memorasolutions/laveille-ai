@extends('backoffice::layouts.admin', ['title' => __('Révision').' #'.$revision->revision_number, 'subtitle' => $article->title])

@section('content')
<div class="card radius-12">
    <div class="card-header border-bottom bg-base py-16 px-24">
        <h6 class="mb-4 d-flex align-items-center gap-2">
            <iconify-icon icon="solar:history-bold" class="icon text-xl"></iconify-icon>
            {{ __('Révision') }} #{{ $revision->revision_number }}
        </h6>
        <p class="text-secondary-light text-sm mb-0">
            {{ $revision->created_at->format('d/m/Y H:i') }} {{ __('par') }} {{ $revision->user->name ?? __('Système') }}
        </p>
    </div>
    <div class="card-body p-24">
        <div class="mb-16">
            <label class="form-label fw-semibold text-secondary-light">{{ __('Titre') }}</label>
            <p class="fw-medium">{{ $revision->title }}</p>
        </div>

        @if($revision->excerpt)
        <div class="mb-16">
            <label class="form-label fw-semibold text-secondary-light">{{ __('Extrait') }}</label>
            <p class="text-secondary-light">{{ $revision->excerpt }}</p>
        </div>
        @endif

        <div class="mb-16">
            <label class="form-label fw-semibold text-secondary-light">{{ __('Contenu') }}</label>
            <div class="border radius-8 p-16">{!! $revision->content !!}</div>
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
