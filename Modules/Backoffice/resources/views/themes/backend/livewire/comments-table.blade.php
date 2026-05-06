<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
<div>
    {{-- Flash Messages --}}
    @if(session('success'))
        <div class="alert alert-success d-flex align-items-center gap-2 mb-3" role="alert">
            <i data-lucide="check-circle" class="icon-sm flex-shrink-0"></i>
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger d-flex align-items-center gap-2 mb-3" role="alert">
            <i data-lucide="alert-triangle" class="icon-sm flex-shrink-0"></i>
            {{ session('error') }}
        </div>
    @endif

    {{-- Bulk Actions Bar --}}
    @if(count($selected) > 0)
        <div class="d-flex flex-wrap align-items-center gap-3 mb-3 px-3 py-2 bg-primary bg-opacity-10 border border-primary border-opacity-25 rounded">
            <span class="fw-medium text-body">{{ count($selected) }} {{ __('sélectionné(s)') }}</span>
            <select wire:model.live="bulkAction" class="form-select form-select-sm w-auto" aria-label="Action groupée">
                <option value="">{{ __('Choisir une action') }}</option>
                <option value="approve">{{ __('Approuver') }}</option>
                <option value="spam">{{ __('Spam') }}</option>
                <option value="delete">{{ __('Supprimer') }}</option>
            </select>
            <button wire:click="executeBulkAction" wire:confirm="{{ __('Confirmer l\'action en masse ?') }}"
                    class="btn btn-sm btn-primary d-inline-flex align-items-center gap-1">
                <i data-lucide="play-circle" class="icon-sm"></i> {{ __('Exécuter') }}
            </button>
        </div>
    @endif

    {{-- Filtres --}}
    <div class="border-bottom pb-3 mb-3">
        <div class="d-flex flex-wrap align-items-center gap-3">
            <div class="input-group input-group-sm w-auto">
                <span class="input-group-text bg-white border-end-0">
                    <i data-lucide="search" class="icon-sm text-muted"></i>
                </span>
                <input type="text" wire:model.live.debounce.300ms="search"
                       class="form-control form-control-sm border-start-0"
                       placeholder="{{ __('Rechercher un commentaire...') }}"
                       aria-label="Rechercher">
            </div>
            <select wire:model.live="filterStatus" class="form-select form-select-sm w-auto" aria-label="Filtrer par statut">
                <option value="">{{ __('Tous les statuts') }}</option>
                <option value="pending">{{ __('En attente') }}</option>
                <option value="approved">{{ __('Approuvé') }}</option>
                <option value="spam">{{ __('Spam') }}</option>
            </select>
            <button wire:click="resetFilters"
                    class="btn btn-sm btn-light d-inline-flex align-items-center gap-1">
                <i data-lucide="refresh-cw" class="icon-sm"></i> {{ __('Réinitialiser') }}
            </button>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-hover">
            <thead>
                <tr class="border-bottom">
                    <th style="width:40px;">
                        <input type="checkbox" wire:model.live="selectAll" class="form-check-input" style="width:16px;height:16px;" aria-label="Tout sélectionner">
                    </th>
                    <th class="fw-medium">{{ __('Auteur') }}</th>
                    <th class="fw-medium">{{ __('Article') }}</th>
                    <th class="fw-medium">{{ __('Commentaire') }}</th>
                    <th class="fw-medium">{{ __('Statut') }}</th>
                    <th class="fw-medium">{{ __('Date') }}</th>
                    <th class="fw-medium">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($comments as $comment)
                <tr>
                    <td>
                        <input type="checkbox" wire:model.live="selected" value="{{ $comment->id }}" class="form-check-input" style="width:16px;height:16px;" aria-label="Sélectionner">
                    </td>
                    <td>
                        <div class="fw-semibold text-body">{{ $comment->authorName() }}</div>
                        @if($comment->guest_email)
                            <small class="text-muted">{{ $comment->guest_email }}</small>
                        @endif
                    </td>
                    <td>
                        @if($comment->article)
                            <a href="{{ route('admin.blog.articles.index') }}" target="_blank"
                               class="text-primary small">
                                {{ \Illuminate\Support\Str::limit($comment->article->title, 30) }}
                            </a>
                        @else
                            <span class="text-muted small">{{ __('Article supprimé') }}</span>
                        @endif
                    </td>
                    <td class="text-muted small">{{ \Illuminate\Support\Str::limit($comment->content, 80) }}</td>
                    <td>
                        <select wire:change="changeStatus({{ $comment->id }}, $event.target.value)"
                                class="form-select form-select-sm w-auto"
                                aria-label="Changer le statut">
                            <option value="pending" @selected($comment->status === 'pending')>{{ __('En attente') }}</option>
                            <option value="approved" @selected($comment->status === 'approved')>{{ __('Approuvé') }}</option>
                            <option value="spam" @selected($comment->status === 'spam')>{{ __('Spam') }}</option>
                        </select>
                    </td>
                    <td class="text-muted small">{{ $comment->created_at->format('d/m/Y H:i') }}</td>
                    <td>
                        {{-- #183 : Bootstrap dropdown standard avec auto-flip Popper.js +
                             kebab 40x40 (WCAG target size). data-bs-display="dynamic"
                             active flip up si pas assez de place en bas. --}}
                        <div class="dropdown">
                            <button type="button"
                                    class="btn btn-light rounded-circle d-inline-flex align-items-center justify-content-center memora-kebab"
                                    data-bs-toggle="dropdown"
                                    data-bs-display="dynamic"
                                    data-bs-auto-close="true"
                                    aria-expanded="false"
                                    aria-label="{{ __('Actions') }}">
                                <i data-lucide="more-vertical" class="icon-md"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end shadow" style="min-width:160px;">
                                <li><button type="button" wire:click="changeStatus({{ $comment->id }}, 'approved')" class="dropdown-item d-flex align-items-center gap-2 text-success">
                                    <i data-lucide="check-circle" class="icon-sm"></i> {{ __('Approuver') }}
                                </button></li>
                                <li><button type="button" wire:click="changeStatus({{ $comment->id }}, 'spam')" class="dropdown-item d-flex align-items-center gap-2 text-warning">
                                    <i data-lucide="alert-triangle" class="icon-sm"></i> {{ __('Spam') }}
                                </button></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><button type="button" wire:click="delete({{ $comment->id }})" wire:confirm="{{ __('Supprimer définitivement ?') }}" class="dropdown-item d-flex align-items-center gap-2 text-danger">
                                    <i data-lucide="trash-2" class="icon-sm"></i> {{ __('Supprimer') }}
                                </button></li>
                            </ul>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="py-5 text-center text-muted">
                        <i data-lucide="message-circle" class="d-block mx-auto mb-2 text-muted" style="width:32px;height:32px;opacity:.4;"></i>
                        {{ __('Aucun commentaire') }}
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mt-3 pt-3 border-top">
        <span class="text-muted small">{{ $comments->total() }} {{ __('commentaire(s)') }}</span>
        {{ $comments->links() }}
    </div>
</div>
