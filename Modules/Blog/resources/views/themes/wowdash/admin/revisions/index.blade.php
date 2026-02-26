@extends('backoffice::layouts.admin', ['title' => 'Historique', 'subtitle' => $article->title])

@section('content')
<div class="card radius-12">
    <div class="card-header border-bottom bg-base py-16 px-24 d-flex flex-wrap gap-2 justify-content-between align-items-center">
        <h6 class="mb-0 d-flex align-items-center gap-2 min-w-0">
            <iconify-icon icon="solar:history-bold" class="icon text-xl flex-shrink-0"></iconify-icon>
            <span class="text-truncate">{{ __('Révisions de') }} "{{ $article->title }}"</span>
        </h6>
        <a href="{{ route('admin.blog.articles.edit', $article) }}" class="btn btn-sm btn-outline-secondary-600 radius-8 d-flex align-items-center gap-2 flex-shrink-0 ms-auto">
            <iconify-icon icon="solar:arrow-left-outline" class="icon"></iconify-icon>
            <span class="d-none d-sm-inline">{{ __('Retour à l\'article') }}</span>
            <span class="d-inline d-sm-none">{{ __('Retour') }}</span>
        </a>
    </div>
    <div class="card-body p-24">
        @if($revisions->isEmpty())
        <div class="text-center py-24">
            <iconify-icon icon="solar:history-outline" class="text-secondary-light" style="font-size: 48px"></iconify-icon>
            <p class="text-secondary-light mt-16">{{ __('Aucune révision pour cet article.') }}</p>
        </div>
        @else
        <div class="table-responsive">
            <table class="table bordered-table sm-table mb-0">
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
                        <td><span class="badge bg-primary-100 text-primary-600">{{ $revision->revision_number }}</span></td>
                        <td>{{ $revision->created_at->format('d/m/Y H:i') }}</td>
                        <td>{{ $revision->user->name ?? __('Système') }}</td>
                        <td>
                            <div class="dropdown">
                                <button class="btn btn-sm btn-outline-secondary-600 radius-8 dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <iconify-icon icon="solar:menu-dots-bold" class="icon"></iconify-icon>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end dropdown-menu-sm-start">
                                    <li>
                                        <a href="{{ route('admin.blog.articles.revisions.show', [$article, $revision]) }}" class="dropdown-item d-flex align-items-center gap-2">
                                            <iconify-icon icon="solar:eye-outline" class="icon"></iconify-icon>
                                            {{ __('Voir') }}
                                        </a>
                                    </li>
                                    <li>
                                        <a href="{{ route('admin.blog.articles.revisions.diff', [$article, $revision]) }}" class="dropdown-item d-flex align-items-center gap-2">
                                            <iconify-icon icon="solar:sort-horizontal-outline" class="icon"></iconify-icon>
                                            {{ __('Comparer') }}
                                        </a>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <form action="{{ route('admin.blog.articles.revisions.restore', [$article, $revision]) }}" method="POST">
                                            @csrf
                                            <button type="submit" class="dropdown-item d-flex align-items-center gap-2 text-warning-600" onclick="return confirm('{{ __('Restaurer cette version ?') }}')">
                                                <iconify-icon icon="solar:restart-outline" class="icon"></iconify-icon>
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
