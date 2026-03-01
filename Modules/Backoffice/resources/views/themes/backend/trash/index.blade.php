<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::themes.backend.layouts.admin', ['title' => $title, 'subtitle' => $subtitle])

@section('content')

<nav class="page-breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('Administration') }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ __('Corbeille') }}</li>
    </ol>
</nav>

{{-- Articles supprimés --}}
<div class="card mb-4">
    <div class="card-header py-3 px-4 border-bottom">
        <h4 class="fw-bold mb-0 d-flex align-items-center gap-2">
            <i data-lucide="file-text" class="icon-md text-muted"></i>
            Articles supprimés ({{ $trashedArticles->count() }})
        </h4>
    </div>
    <div class="card-body p-4">
        @if($trashedArticles->isEmpty())
            <div class="text-center py-5">
                <i data-lucide="file-text" class="d-block mx-auto mb-3 text-muted" style="width:48px;height:48px;opacity:.3;"></i>
                <p class="text-muted small">Aucun article dans la corbeille.</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th class="py-3 px-4 fw-semibold text-body">Titre</th>
                            <th class="py-3 px-4 fw-semibold text-body">Statut</th>
                            <th class="py-3 px-4 fw-semibold text-body">Supprimé le</th>
                            <th class="py-3 px-4 fw-semibold text-body">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($trashedArticles as $article)
                        <tr>
                            <td class="py-3 px-4 fw-medium text-body">{{ $article->title }}</td>
                            <td class="py-3 px-4">
                                <span class="badge bg-secondary bg-opacity-10 text-secondary">
                                    {{ $article->status }}
                                </span>
                            </td>
                            <td class="py-3 px-4 text-muted small">
                                {{ $article->deleted_at->format('d/m/Y H:i') }}
                            </td>
                            <td class="py-3 px-4">
                                <div class="dropdown">
                                    <button class="btn btn-light btn-sm d-inline-flex align-items-center justify-content-center"
                                            style="width:36px;height:36px;"
                                            type="button"
                                            data-bs-toggle="dropdown">
                                        <i data-lucide="more-vertical" class="icon-sm"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                        <li>
                                            <form action="{{ route('admin.trash.restore-article', $article->id) }}" method="POST">
                                                @csrf
                                                <button type="submit" class="dropdown-item d-flex align-items-center gap-2">
                                                    <i data-lucide="rotate-ccw" class="icon-sm"></i>
                                                    Restaurer
                                                </button>
                                            </form>
                                        </li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <form action="{{ route('admin.trash.force-delete-article', $article->id) }}" method="POST">
                                                @csrf @method('DELETE')
                                                <button type="submit"
                                                        onclick="return confirm('Supprimer définitivement ?')"
                                                        class="dropdown-item text-danger d-flex align-items-center gap-2">
                                                    <i data-lucide="trash-2" class="icon-sm"></i>
                                                    Supprimer
                                                </button>
                                            </form>
                                        </li>
                                    </ul>
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

{{-- Commentaires supprimés --}}
<div class="card">
    <div class="card-header py-3 px-4 border-bottom">
        <h4 class="fw-bold mb-0 d-flex align-items-center gap-2">
            <i data-lucide="message-circle" class="icon-md text-muted"></i>
            Commentaires supprimés ({{ $trashedComments->count() }})
        </h4>
    </div>
    <div class="card-body p-4">
        @if($trashedComments->isEmpty())
            <div class="text-center py-5">
                <i data-lucide="message-circle" class="d-block mx-auto mb-3 text-muted" style="width:48px;height:48px;opacity:.3;"></i>
                <p class="text-muted small">Aucun commentaire dans la corbeille.</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th class="py-3 px-4 fw-semibold text-body">Contenu</th>
                            <th class="py-3 px-4 fw-semibold text-body">Article</th>
                            <th class="py-3 px-4 fw-semibold text-body">Supprimé le</th>
                            <th class="py-3 px-4 fw-semibold text-body">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($trashedComments as $comment)
                        <tr>
                            <td class="py-3 px-4 fw-medium text-body">
                                {{ \Illuminate\Support\Str::limit($comment->content, 60) }}
                            </td>
                            <td class="py-3 px-4 text-muted small">
                                {{ $comment->article?->title ?? 'N/A' }}
                            </td>
                            <td class="py-3 px-4 text-muted small">
                                {{ $comment->deleted_at->format('d/m/Y H:i') }}
                            </td>
                            <td class="py-3 px-4">
                                <div class="dropdown">
                                    <button class="btn btn-light btn-sm d-inline-flex align-items-center justify-content-center"
                                            style="width:36px;height:36px;"
                                            type="button"
                                            data-bs-toggle="dropdown">
                                        <i data-lucide="more-vertical" class="icon-sm"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                        <li>
                                            <form action="{{ route('admin.trash.restore-comment', $comment->id) }}" method="POST">
                                                @csrf
                                                <button type="submit" class="dropdown-item d-flex align-items-center gap-2">
                                                    <i data-lucide="rotate-ccw" class="icon-sm"></i>
                                                    Restaurer
                                                </button>
                                            </form>
                                        </li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <form action="{{ route('admin.trash.force-delete-comment', $comment->id) }}" method="POST">
                                                @csrf @method('DELETE')
                                                <button type="submit"
                                                        onclick="return confirm('Supprimer définitivement ?')"
                                                        class="dropdown-item text-danger d-flex align-items-center gap-2">
                                                    <i data-lucide="trash-2" class="icon-sm"></i>
                                                    Supprimer
                                                </button>
                                            </form>
                                        </li>
                                    </ul>
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
