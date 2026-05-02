<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::themes.backend.layouts.admin', ['title' => 'Historique', 'subtitle' => $article->title])

@section('breadcrumbs')
<nav class="page-breadcrumb" aria-label="Fil d'Ariane">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Administration</a></li>
        <li class="breadcrumb-item">Blog</li>
        <li class="breadcrumb-item active" aria-current="page">Revisions</li>
    </ol>
</nav>
@endsection

@section('content')

<div class="card">
    <div class="card-header py-3 px-4 border-bottom">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
            <h4 class="fw-bold mb-0 d-flex align-items-center gap-2" style="min-width:0;">
                <i data-lucide="history" class="flex-shrink-0"></i>
                <span class="text-truncate">{{ __('Révisions de') }} "{{ $article->title }}"</span>
            </h4>
            <div class="d-flex align-items-center gap-2 flex-shrink-0">
                <x-backoffice::help-modal id="helpRevisionsModal" :title="__('Historique des révisions')" icon="history" :buttonLabel="__('Aide')">
                    @include('blog::themes.backend.admin.revisions._help')
                </x-backoffice::help-modal>
                <a href="{{ route('admin.blog.articles.edit', $article) }}"
                   class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-2">
                    <i data-lucide="arrow-left" class="icon-sm"></i>
                    <span class="d-none d-sm-inline">{{ __('Retour à l\'article') }}</span>
                    <span class="d-sm-none">{{ __('Retour') }}</span>
                </a>
            </div>
        </div>
    </div>
    <div class="card-body p-4">
        @if($revisions->isEmpty())
            <div class="text-center py-5">
                <i data-lucide="history" class="text-muted d-block mx-auto mb-4" style="width:64px;height:64px;"></i>
                <p class="text-muted">{{ __('Aucune révision pour cet article.') }}</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="fw-medium py-3 px-3">#</th>
                            <th class="fw-medium py-3 px-3">{{ __('Date') }}</th>
                            <th class="fw-medium py-3 px-3">{{ __('Auteur') }}</th>
                            <th class="fw-medium py-3 px-3 text-end">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($revisions as $revision)
                        <tr>
                            <td class="py-3 px-3">
                                <span class="badge bg-primary bg-opacity-10 text-primary">{{ $revision->revision_number }}</span>
                            </td>
                            <td class="py-3 px-3 text-muted">{{ $revision->created_at->format('d/m/Y H:i') }}</td>
                            <td class="py-3 px-3 text-muted">{{ $revision->user->name ?? __('Système') }}</td>
                            <td class="py-3 px-3">
                                <div class="d-flex align-items-center justify-content-end gap-2">
                                    <a href="{{ route('admin.blog.articles.revisions.show', [$article, $revision]) }}"
                                       class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1">
                                        <i data-lucide="eye" class="icon-sm"></i>
                                        {{ __('Voir') }}
                                    </a>
                                    <a href="{{ route('admin.blog.articles.revisions.diff', [$article, $revision]) }}"
                                       class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1">
                                        <i data-lucide="git-compare" class="icon-sm"></i>
                                        {{ __('Comparer') }}
                                    </a>
                                    <form action="{{ route('admin.blog.articles.revisions.restore', [$article, $revision]) }}" method="POST" class="d-inline" x-data>
                                        @csrf
                                        <button type="button"
                                                class="btn btn-sm btn-outline-warning d-inline-flex align-items-center gap-1"
                                                @click="$dispatch('confirm-action', { title: @js(__('Confirmer')), message: @js(__('Restaurer cette version ?')), action: () => $el.closest('form').submit() })">
                                            <i data-lucide="undo-2" class="icon-sm"></i>
                                            {{ __('Restaurer') }}
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>

@endsection
