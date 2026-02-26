<div>
    {{-- Flash Messages --}}
    @if(session('success'))
        <div class="alert alert-success d-flex align-items-center gap-2 mb-20">
            <iconify-icon icon="solar:check-circle-outline" class="icon text-lg"></iconify-icon>
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger d-flex align-items-center gap-2 mb-20">
            <iconify-icon icon="solar:danger-circle-outline" class="icon text-lg"></iconify-icon>
            {{ session('error') }}
        </div>
    @endif

    {{-- Bulk Actions Bar --}}
    @if(count($selected) > 0)
        <div class="d-flex align-items-center gap-3 mb-20 p-12 bg-primary-50 rounded-8 border border-primary-100">
            <span class="text-sm fw-medium">{{ count($selected) }} sélectionné(s)</span>
            <select wire:model.live="bulkAction" class="form-select form-select-sm w-auto">
                <option value="">Choisir une action</option>
                <option value="approve">{{ __('Approuver') }}</option>
                <option value="spam">{{ __('Spam') }}</option>
                <option value="delete">{{ __('Supprimer') }}</option>
            </select>
            <button wire:click="executeBulkAction" wire:confirm="Confirmer l'action en masse ?" class="btn btn-sm btn-primary-600 d-inline-flex align-items-center gap-1">
                <iconify-icon icon="solar:play-circle-outline" class="icon text-xl"></iconify-icon> Exécuter
            </button>
        </div>
    @endif

    <div class="card-body border-bottom pb-16">
        <div class="d-flex flex-wrap align-items-center gap-3 mb-20">
            <form class="navbar-search">
                <input type="text" wire:model.live.debounce.300ms="search" class="bg-base h-40-px w-auto" placeholder="Rechercher un commentaire...">
                <iconify-icon icon="ion:search-outline" class="icon"></iconify-icon>
            </form>
            <select wire:model.live="filterStatus" class="form-select form-select-sm w-auto ps-12 py-6 radius-12 h-40-px">
                <option value="">Tous les statuts</option>
                <option value="pending">En attente</option>
                <option value="approved">Approuvé</option>
                <option value="spam">Spam</option>
            </select>
            <button wire:click="resetFilters" class="btn btn-sm text-neutral-600 bg-neutral-100 bg-hover-neutral-200 radius-4">
                Réinitialiser
            </button>
        </div>
    </div>

    <div class="table-responsive scroll-sm">
        <table class="table bordered-table sm-table mb-0">
            <thead>
                <tr>
                    <th style="width:40px"><input type="checkbox" wire:model.live="selectAll" class="form-check-input"></th>
                    <th>Auteur</th>
                    <th>Article</th>
                    <th>Commentaire</th>
                    <th>Statut</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($comments as $comment)
                <tr>
                    <td><input type="checkbox" wire:model.live="selected" value="{{ $comment->id }}" class="form-check-input"></td>
                    <td>
                        <div class="fw-semibold">{{ $comment->authorName() }}</div>
                        @if($comment->guest_email)
                            <small class="text-neutral-400">{{ $comment->guest_email }}</small>
                        @endif
                    </td>
                    <td>
                        @if($comment->article)
                            <a href="{{ route('blog.show', $comment->article->slug) }}" target="_blank">
                                {{ \Illuminate\Support\Str::limit($comment->article->title, 30) }}
                            </a>
                        @else
                            <span class="text-neutral-400">Article supprimé</span>
                        @endif
                    </td>
                    <td>{{ \Illuminate\Support\Str::limit($comment->content, 80) }}</td>
                    <td>
                        <select wire:change="changeStatus({{ $comment->id }}, $event.target.value)" class="form-select form-select-sm radius-4" style="width:auto;min-width:120px;">
                            <option value="pending" @selected($comment->status === 'pending')>En attente</option>
                            <option value="approved" @selected($comment->status === 'approved')>Approuvé</option>
                            <option value="spam" @selected($comment->status === 'spam')>Spam</option>
                        </select>
                    </td>
                    <td>{{ $comment->created_at->format('d/m/Y H:i') }}</td>
                    <td>
                        <div class="dropdown">
                            <button type="button" class="w-40-px h-40-px bg-neutral-200 text-secondary-light rounded-circle d-flex justify-content-center align-items-center" data-bs-toggle="dropdown" aria-expanded="false">
                                <iconify-icon icon="tabler:dots-vertical" class="icon text-xl"></iconify-icon>
                            </button>
                            <div class="dropdown-menu dropdown-menu-end p-12">
                                <button wire:click="delete({{ $comment->id }})" wire:confirm="Supprimer definitivement ?" class="dropdown-item d-flex align-items-center gap-2 px-12 py-8 rounded-4 text-sm text-danger-600">
                                    <iconify-icon icon="fluent:delete-24-regular" class="icon text-lg"></iconify-icon> Supprimer
                                </button>
                            </div>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center text-neutral-600 py-32">
                        <iconify-icon icon="solar:chat-line-outline" class="icon text-4xl mb-2 d-block"></iconify-icon>
                        Aucun commentaire
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="card-footer d-flex align-items-center justify-content-between flex-wrap gap-2">
        <span class="text-neutral-600 text-sm">{{ $comments->total() }} commentaire(s)</span>
        {{ $comments->links() }}
    </div>
</div>
