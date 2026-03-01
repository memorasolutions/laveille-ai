@extends('backoffice::layouts.admin', ['title' => 'Historique', 'subtitle' => $article->title])

@section('content')
<div class="card">
    <div class="card-header border-bottom py-3 px-4 d-flex flex-wrap gap-2 justify-content-between align-items-center">
        <h6 class="mb-0 d-flex align-items-center gap-2 min-w-0">
            <i data-lucide="history" class="flex-shrink-0"></i>
            <span class="text-truncate">{{ __('Révisions de') }} "{{ $article->title }}"</span>
        </h6>
        <a href="{{ route('admin.blog.articles.edit', $article) }}" class="btn btn-sm btn-outline-secondary rounded-2 d-flex align-items-center gap-2 flex-shrink-0 ms-auto">
            <i data-lucide="arrow-left"></i>
            <span class="d-none d-sm-inline">{{ __('Retour à l\'article') }}</span>
            <span class="d-inline d-sm-none">{{ __('Retour') }}</span>
        </a>
    </div>
    <div class="card-body p-4">
        @if($revisions->isEmpty())
        <div class="text-center py-4">
            <i data-lucide="history" class="text-muted d-block mx-auto mb-2" style="width:48px;height:48px;"></i>
            <p class="text-muted mt-3">{{ __('Aucune révision pour cet article.') }}</p>
        </div>
        @else
        <div class="table-responsive">
            <table class="table table-sm mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>{{ __('Date') }}</th>
                        <th>{{ __('Auteur') }}</th>
                        <th>{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($revisions as $revision)
                    <tr>
                        <td><span class="badge bg-primary bg-opacity-10 text-primary">{{ $revision->revision_number }}</span></td>
                        <td>{{ $revision->created_at->format('d/m/Y H:i') }}</td>
                        <td>{{ $revision->user->name ?? __('Système') }}</td>
                        <td>
                            <div class="dropdown">
                                <button class="btn btn-sm btn-outline-secondary rounded-2 dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i data-lucide="more-horizontal"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end dropdown-menu-sm-start">
                                    <li>
                                        <a href="{{ route('admin.blog.articles.revisions.show', [$article, $revision]) }}" class="dropdown-item d-flex align-items-center gap-2">
                                            <i data-lucide="eye"></i>
                                            {{ __('Voir') }}
                                        </a>
                                    </li>
                                    <li>
                                        <a href="{{ route('admin.blog.articles.revisions.diff', [$article, $revision]) }}" class="dropdown-item d-flex align-items-center gap-2">
                                            <i data-lucide="git-compare"></i>
                                            {{ __('Comparer') }}
                                        </a>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <form action="{{ route('admin.blog.articles.revisions.restore', [$article, $revision]) }}" method="POST">
                                            @csrf
                                            <button type="submit" class="dropdown-item d-flex align-items-center gap-2 text-warning" onclick="return confirm('{{ __('Restaurer cette version ?') }}')">
                                                <i data-lucide="refresh-cw"></i>
                                                {{ __('Restaurer') }}
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
