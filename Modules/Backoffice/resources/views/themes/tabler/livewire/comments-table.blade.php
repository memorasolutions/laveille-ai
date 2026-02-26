<div>
    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
        <div class="d-flex"><i class="ti ti-check me-2"></i>{{ session('success') }}</div>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif
    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show">
        <div class="d-flex"><i class="ti ti-alert-circle me-2"></i>{{ session('error') }}</div>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    <div class="row mb-3 g-2">
        <div class="col-md-5">
            <div class="input-icon">
                <span class="input-icon-addon"><i class="ti ti-search"></i></span>
                <input type="text" wire:model.live.debounce.300ms="search" class="form-control" placeholder="Rechercher un commentaire...">
            </div>
        </div>
        <div class="col-md-4">
            <select wire:model.live="filterStatus" class="form-select">
                <option value="">Tous les statuts</option>
                <option value="pending">En attente</option>
                <option value="approved">Approuvé</option>
                <option value="rejected">Rejeté</option>
                <option value="spam">Spam</option>
            </select>
        </div>
        <div class="col-md-3">
            <button wire:click="resetFilters" class="btn btn-outline-secondary w-100">
                <i class="ti ti-x me-1"></i> Reset
            </button>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-vcenter table-hover">
            <thead>
                <tr>
                    <th>Auteur</th>
                    <th>Commentaire</th>
                    <th>Article</th>
                    <th>Statut</th>
                    <th wire:click="sort('created_at')" style="cursor:pointer">
                        Date <i class="ti ti-arrows-sort {{ ($sortBy ?? '') === 'created_at' ? 'text-primary' : 'text-muted' }}"></i>
                    </th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($comments as $comment)
                <tr>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <span class="avatar avatar-xs rounded-circle bg-primary-lt"
                                style="font-size:.65rem; width:24px; height:24px; display:inline-flex; align-items:center; justify-content:center;">
                                {{ strtoupper(substr($comment->author_name ?? $comment->user?->name ?? 'A', 0, 1)) }}
                            </span>
                            <div>
                                <div class="small fw-medium">{{ $comment->author_name ?? $comment->user?->name ?? 'Anonyme' }}</div>
                                @if($comment->author_email)
                                <small class="text-muted">{{ $comment->author_email }}</small>
                                @endif
                            </div>
                        </div>
                    </td>
                    <td style="max-width: 240px;">
                        <p class="mb-0 small text-truncate" title="{{ $comment->content }}">
                            {{ $comment->content }}
                        </p>
                    </td>
                    <td style="max-width: 180px;">
                        @if($comment->article)
                        <a href="{{ route('admin.articles.edit', $comment->article) }}" class="text-truncate small d-block text-decoration-none">
                            {{ $comment->article->getTranslation('title', app()->getLocale()) }}
                        </a>
                        @else
                        <span class="text-muted small">—</span>
                        @endif
                    </td>
                    <td>
                        @php
                            $statusMap = [
                                'pending'  => ['bg-warning-lt text-warning', 'ti-clock', 'En attente'],
                                'approved' => ['bg-success-lt text-success', 'ti-circle-check', 'Approuvé'],
                                'rejected' => ['bg-danger-lt text-danger', 'ti-circle-x', 'Rejeté'],
                                'spam'     => ['bg-red-lt text-red', 'ti-ban', 'Spam'],
                            ];
                            [$cls, $icon, $label] = $statusMap[$comment->status] ?? ['bg-secondary-lt', 'ti-question-mark', ucfirst($comment->status ?? '')];
                        @endphp
                        <span class="badge {{ $cls }}">
                            <i class="ti {{ $icon }} me-1"></i>{{ $label }}
                        </span>
                    </td>
                    <td class="text-muted small">{{ $comment->created_at->format('d/m/Y H:i') }}</td>
                    <td>
                        <div class="d-flex gap-1">
                            @if($comment->status !== 'approved')
                            <button wire:click="approveComment({{ $comment->id }})"
                                class="btn btn-sm btn-outline-success" title="Approuver">
                                <i class="ti ti-check"></i>
                            </button>
                            @endif
                            @if($comment->status !== 'rejected')
                            <button wire:click="rejectComment({{ $comment->id }})"
                                class="btn btn-sm btn-outline-warning" title="Rejeter">
                                <i class="ti ti-x"></i>
                            </button>
                            @endif
                            <button wire:click="deleteComment({{ $comment->id }})"
                                wire:confirm="Supprimer ce commentaire ?"
                                class="btn btn-sm btn-outline-danger" title="Supprimer">
                                <i class="ti ti-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center text-muted py-4">
                        <i class="ti ti-message-off fs-2 d-block mb-2"></i>
                        Aucun commentaire trouvé
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="d-flex align-items-center justify-content-between mt-3">
        <div class="text-muted small">{{ $comments->total() }} commentaire(s) au total</div>
        <div>{{ $comments->links() }}</div>
    </div>
</div>
