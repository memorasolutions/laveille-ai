<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Backoffice\Livewire;

use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Modules\Backoffice\Livewire\Concerns\HasBulkActions;
use Modules\Blog\Models\Comment;
use Modules\Blog\States\ApprovedCommentState;
use Modules\Blog\States\PendingCommentState;
use Modules\Blog\States\SpamCommentState;
use Spatie\ModelStates\Exceptions\CouldNotPerformTransition;

class CommentsTable extends Component
{
    use HasBulkActions, WithPagination;

    protected string $paginationTheme = 'bootstrap';

    #[Url]
    public string $search = '';

    #[Url]
    public string $filterStatus = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingFilterStatus(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->search = '';
        $this->filterStatus = '';
        $this->resetPage();
    }

    public function changeStatus(int $commentId, string $status): void
    {
        $stateMap = [
            'pending' => PendingCommentState::class,
            'approved' => ApprovedCommentState::class,
            'spam' => SpamCommentState::class,
        ];

        if (! isset($stateMap[$status])) {
            return;
        }

        $comment = Comment::findOrFail($commentId);

        try {
            $comment->status->transitionTo($stateMap[$status]);
            $this->dispatch('toast', type: 'success', message: "Commentaire #{$comment->id} → {$status}.");
        } catch (CouldNotPerformTransition) {
            $this->dispatch('toast', type: 'error', message: "Transition vers « {$status} » non autorisée.");
        }
    }

    public function delete(int $id): void
    {
        Comment::withTrashed()->find($id)?->forceDelete();
    }

    protected function getBulkActions(): array
    {
        return [
            'approve' => __('Approuver'),
            'spam' => __('Spam'),
            'delete' => __('Supprimer'),
        ];
    }

    protected function handleBulkAction(string $action, array $ids): void
    {
        $stateMap = [
            'approve' => ApprovedCommentState::class,
            'spam' => SpamCommentState::class,
        ];

        if ($action === 'delete') {
            Comment::withTrashed()->whereIn('id', $ids)->forceDelete();

            return;
        }

        if (isset($stateMap[$action])) {
            foreach (Comment::whereIn('id', $ids)->get() as $comment) {
                try {
                    $comment->status->transitionTo($stateMap[$action]);
                } catch (CouldNotPerformTransition) {
                    // Skip comments that can't transition
                }
            }
        }
    }

    protected function getBulkPageIds(): array
    {
        return Comment::query()
            ->withTrashed()
            ->when($this->search, fn ($q) => $q->where(fn ($q2) => $q2
                ->where('content', 'like', '%'.$this->search.'%')
                ->orWhere('guest_name', 'like', '%'.$this->search.'%')
                ->orWhere('guest_email', 'like', '%'.$this->search.'%')
            ))
            ->when($this->filterStatus, fn ($q) => $q->where('status', $this->filterStatus))
            ->latest()
            ->paginate(20)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->toArray();
    }

    public function render(): \Illuminate\View\View
    {
        $comments = Comment::with(['article', 'author'])
            ->withTrashed()
            ->when($this->search, fn ($q) => $q->where(fn ($q2) => $q2
                ->where('content', 'like', '%'.$this->search.'%')
                ->orWhere('guest_name', 'like', '%'.$this->search.'%')
                ->orWhere('guest_email', 'like', '%'.$this->search.'%')
            ))
            ->when($this->filterStatus, fn ($q) => $q->where('status', $this->filterStatus))
            ->latest()
            ->paginate(20);

        return view('backoffice::livewire.comments-table', compact('comments'));
    }
}
