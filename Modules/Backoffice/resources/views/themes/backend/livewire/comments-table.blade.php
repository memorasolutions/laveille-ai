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
                            {{-- #194 : lien vers l'article frontend (pas admin index) --}}
                            <a href="{{ route('blog.show', $comment->article->slug) }}#comments" target="_blank" rel="noopener"
                               class="text-primary small d-inline-flex align-items-center gap-1"
                               title="{{ $comment->article->title }}">
                                {{ \Illuminate\Support\Str::limit($comment->article->title, 30) }}
                                <i data-lucide="external-link" style="width:14px;height:14px;opacity:0.7;"></i>
                            </a>
                        @else
                            <span class="text-muted small">{{ __('Article supprimé') }}</span>
                        @endif
                    </td>
                    <td>
                        {{-- #184 : snippet cliquable -> modal detail --}}
                        <button type="button"
                                class="btn btn-link p-0 text-start text-muted small text-decoration-none lh-sm"
                                data-bs-toggle="modal"
                                data-bs-target="#commentDetail{{ $comment->id }}"
                                title="{{ Str::limit($comment->content, 250) }}"
                                style="white-space:normal;max-width:280px;">
                            {{ Str::limit($comment->content, 80) }}
                            <i data-lucide="eye" class="icon-sm ms-1 text-primary"></i>
                        </button>
                        {{-- Modal detail commentaire --}}
                        <div class="modal fade" id="commentDetail{{ $comment->id }}" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered modal-lg">
                                <div class="modal-content" style="border-radius:12px;border:none;">
                                    <div class="modal-header" style="background:linear-gradient(135deg,#0B7285 0%,#053d4a 100%);color:#fff;border-bottom:none;">
                                        <h5 class="modal-title">{{ __('Commentaire') }} #{{ $comment->id }}</h5>
                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
                                    </div>
                                    <div class="modal-body">
                                        <dl class="row mb-3">
                                            <dt class="col-sm-3 text-muted">{{ __('Auteur') }}</dt>
                                            <dd class="col-sm-9 fw-semibold">
                                                {{ $comment->authorName() }}
                                                @if($comment->user_id) <span class="badge bg-primary ms-1">{{ __('Membre') }}</span>
                                                @else <span class="badge bg-secondary ms-1">{{ __('Visiteur') }}</span>
                                                @endif
                                            </dd>
                                            <dt class="col-sm-3 text-muted">{{ __('Article') }}</dt>
                                            <dd class="col-sm-9">
                                                @if($comment->article)
                                                    <a href="{{ route('blog.show', $comment->article->slug) }}#comments" target="_blank" rel="noopener" class="d-inline-flex align-items-center gap-1">
                                                        {{ $comment->article->title }}
                                                        <i data-lucide="external-link" style="width:14px;height:14px;opacity:0.7;"></i>
                                                    </a>
                                                @else
                                                    <em class="text-muted">{{ __('Article supprimé / Type non-article') }}</em>
                                                @endif
                                            </dd>
                                            <dt class="col-sm-3 text-muted">{{ __('Statut') }}</dt>
                                            <dd class="col-sm-9">
                                                @php($badge = ['pending'=>'warning','approved'=>'success','spam'=>'danger','rejected'=>'dark'][$comment->status] ?? 'secondary')
                                                <span class="badge bg-{{ $badge }}">{{ ucfirst((string) $comment->status) }}</span>
                                            </dd>
                                            <dt class="col-sm-3 text-muted">{{ __('Date') }}</dt>
                                            <dd class="col-sm-9">{{ $comment->created_at->format('d/m/Y H:i:s') }}</dd>
                                        </dl>
                                        <hr>
                                        <h6 class="text-muted small text-uppercase mb-2">{{ __('Contenu') }}</h6>
                                        <div style="white-space:pre-wrap;background:#f8fafc;padding:1rem;border-radius:8px;border:1px solid #e2e8f0;">{{ $comment->content }}</div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Fermer') }}</button>
                                        <button type="button" wire:click="changeStatus({{ $comment->id }}, 'approved')" class="btn btn-success" data-bs-dismiss="modal">
                                            <i data-lucide="check-circle" class="icon-sm me-1"></i> {{ __('Approuver') }}
                                        </button>
                                        <button type="button" wire:click="changeStatus({{ $comment->id }}, 'spam')" class="btn btn-warning" data-bs-dismiss="modal">
                                            <i data-lucide="alert-triangle" class="icon-sm me-1"></i> {{ __('Spam') }}
                                        </button>
                                        <button type="button" wire:click="delete({{ $comment->id }})" wire:confirm="{{ __('Supprimer définitivement ?') }}" class="btn btn-danger" data-bs-dismiss="modal">
                                            <i data-lucide="trash-2" class="icon-sm me-1"></i> {{ __('Supprimer') }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </td>
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
                                    data-bs-strategy="fixed"
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
