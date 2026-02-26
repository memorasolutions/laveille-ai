@extends('backoffice::layouts.admin', ['title' => $title, 'subtitle' => $subtitle])

@section('content')

{{-- Articles supprimés --}}
<div class="card mb-24">
    <div class="card-header">
        <h6 class="mb-0">Articles supprimés ({{ $trashedArticles->count() }})</h6>
    </div>
    <div class="card-body p-0">
        @if($trashedArticles->isEmpty())
            <div class="text-center py-32">
                <iconify-icon icon="solar:document-text-outline" class="icon text-neutral-400" style="font-size: 40px"></iconify-icon>
                <p class="mt-3 text-neutral-600">Aucun article dans la corbeille.</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-striped mb-0">
                    <thead>
                        <tr>
                            <th>Titre</th>
                            <th>Statut</th>
                            <th>Supprimé le</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($trashedArticles as $article)
                            <tr>
                                <td>{{ $article->title }}</td>
                                <td><span class="badge bg-neutral-200 text-neutral-600">{{ $article->status }}</span></td>
                                <td>{{ $article->deleted_at->format('d/m/Y H:i') }}</td>
                                <td>
                                    <div class="dropdown">
                                        <button type="button" class="w-40-px h-40-px bg-neutral-200 text-secondary-light rounded-circle d-flex justify-content-center align-items-center" data-bs-toggle="dropdown">
                                            <iconify-icon icon="tabler:dots-vertical" class="icon text-xl"></iconify-icon>
                                        </button>
                                        <div class="dropdown-menu dropdown-menu-end p-12">
                                            <form action="{{ route('admin.trash.restore-article', $article->id) }}" method="POST">
                                                @csrf
                                                <button type="submit" class="dropdown-item d-flex align-items-center gap-2">
                                                    <iconify-icon icon="solar:refresh-outline" class="icon"></iconify-icon> Restaurer
                                                </button>
                                            </form>
                                            <form action="{{ route('admin.trash.force-delete-article', $article->id) }}" method="POST">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="dropdown-item d-flex align-items-center gap-2 text-danger-600" onclick="return confirm('Supprimer définitivement ?')">
                                                    <iconify-icon icon="solar:trash-bin-trash-outline" class="icon"></iconify-icon> Supprimer
                                                </button>
                                            </form>
                                        </div>
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
    <div class="card-header">
        <h6 class="mb-0">Commentaires supprimés ({{ $trashedComments->count() }})</h6>
    </div>
    <div class="card-body p-0">
        @if($trashedComments->isEmpty())
            <div class="text-center py-32">
                <iconify-icon icon="solar:chat-round-dots-outline" class="icon text-neutral-400" style="font-size: 40px"></iconify-icon>
                <p class="mt-3 text-neutral-600">Aucun commentaire dans la corbeille.</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-striped mb-0">
                    <thead>
                        <tr>
                            <th>Contenu</th>
                            <th>Article</th>
                            <th>Supprimé le</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($trashedComments as $comment)
                            <tr>
                                <td>{{ \Illuminate\Support\Str::limit($comment->content, 60) }}</td>
                                <td>{{ $comment->article?->title ?? 'N/A' }}</td>
                                <td>{{ $comment->deleted_at->format('d/m/Y H:i') }}</td>
                                <td>
                                    <div class="dropdown">
                                        <button type="button" class="w-40-px h-40-px bg-neutral-200 text-secondary-light rounded-circle d-flex justify-content-center align-items-center" data-bs-toggle="dropdown">
                                            <iconify-icon icon="tabler:dots-vertical" class="icon text-xl"></iconify-icon>
                                        </button>
                                        <div class="dropdown-menu dropdown-menu-end p-12">
                                            <form action="{{ route('admin.trash.restore-comment', $comment->id) }}" method="POST">
                                                @csrf
                                                <button type="submit" class="dropdown-item d-flex align-items-center gap-2">
                                                    <iconify-icon icon="solar:refresh-outline" class="icon"></iconify-icon> Restaurer
                                                </button>
                                            </form>
                                            <form action="{{ route('admin.trash.force-delete-comment', $comment->id) }}" method="POST">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="dropdown-item d-flex align-items-center gap-2 text-danger-600" onclick="return confirm('Supprimer définitivement ?')">
                                                    <iconify-icon icon="solar:trash-bin-trash-outline" class="icon"></iconify-icon> Supprimer
                                                </button>
                                            </form>
                                        </div>
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
