<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Backoffice\Livewire;

use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Modules\Community\Models\Comment;
use Modules\Settings\Facades\Settings;
use Modules\Core\Traits\HasBulkActions;

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
        // #177 : pivot vers community_comments (Modules\Community\Models\Comment).
        // Plus de Spatie ModelStates - simple update enum.
        if (! in_array($status, ['pending', 'approved', 'rejected', 'spam'], true)) {
            return;
        }

        $comment = Comment::findOrFail($commentId);
        $comment->update(['status' => $status]);

        $this->dispatch('toast', type: 'success', message: "Commentaire #{$comment->id} → {$status}.");
    }

    public function delete(int $id): void
    {
        Comment::find($id)?->delete();
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
        $statusMap = [
            'approve' => 'approved',
            'spam' => 'spam',
        ];

        if ($action === 'delete') {
            Comment::whereIn('id', $ids)->delete();
            return;
        }

        if (isset($statusMap[$action])) {
            Comment::whereIn('id', $ids)->update(['status' => $statusMap[$action]]);
        }
    }

    protected function getBulkPageIds(): array
    {
        return Comment::query()
            ->when($this->search, fn ($q) => $q->where(fn ($q2) => $q2
                ->where('content', 'like', '%'.$this->search.'%')
                ->orWhere('guest_name', 'like', '%'.$this->search.'%')
            ))
            ->when($this->filterStatus, fn ($q) => $q->where('status', $this->filterStatus))
            ->latest()
            ->paginate((int) Settings::get('backoffice.comments_per_page', 20))
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->toArray();
    }

    public function render(): \Illuminate\View\View
    {
        // #177 : community_comments est polymorphic (commentable_type/id).
        // with('commentable') eager-load article/dictionary/etc selon type.
        $comments = Comment::with(['commentable', 'user'])
            ->when($this->search, fn ($q) => $q->where(fn ($q2) => $q2
                ->where('content', 'like', '%'.$this->search.'%')
                ->orWhere('guest_name', 'like', '%'.$this->search.'%')
            ))
            ->when($this->filterStatus, fn ($q) => $q->where('status', $this->filterStatus))
            ->latest()
            ->paginate((int) Settings::get('backoffice.comments_per_page', 20));

        return view('backoffice::livewire.comments-table', compact('comments'));
    }
}
