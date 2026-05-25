<?php

declare(strict_types=1);

namespace Modules\Authors\Livewire;

use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Livewire\WithPagination;
use Modules\Authors\Models\AuthorComment;

final class CommentModerationQueue extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'tailwind';

    public string $status = 'pending';

    public string $search = '';

    public array $selected = [];

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->selected = [];
        $this->resetPage();
    }

    public function approve(int $id): void
    {
        $comment = AuthorComment::find($id);
        if ($comment !== null) {
            $comment->update(['approved_at' => now(), 'flagged_at' => null]);
            Log::channel('daily')->info('authors.comment.approved', ['comment_id' => $id, 'user_id' => auth()->id()]);
            $this->dispatch('toast-show', message: 'Commentaire approuvé.', variant: 'success');
        }
    }

    public function markSpam(int $id): void
    {
        $comment = AuthorComment::find($id);
        if ($comment !== null) {
            $comment->update(['flagged_at' => now(), 'spam_score' => 100, 'approved_at' => null]);
            Log::channel('daily')->info('authors.comment.spam', ['comment_id' => $id, 'user_id' => auth()->id()]);
            $this->dispatch('toast-show', message: 'Commentaire marqué comme spam.', variant: 'warning');
        }
    }

    public function deleteSoft(int $id): void
    {
        $comment = AuthorComment::find($id);
        if ($comment !== null) {
            $comment->delete();
            Log::channel('daily')->info('authors.comment.deleted', ['comment_id' => $id, 'user_id' => auth()->id()]);
            $this->dispatch('toast-show', message: 'Commentaire supprimé.', variant: 'info');
        }
    }

    public function bulkApprove(): void
    {
        if ($this->selected === []) {
            return;
        }
        AuthorComment::whereIn('id', $this->selected)->update(['approved_at' => now(), 'flagged_at' => null]);
        Log::channel('daily')->info('authors.comment.bulk_approved', ['ids' => $this->selected, 'user_id' => auth()->id()]);
        $count = count($this->selected);
        $this->selected = [];
        $this->dispatch('toast-show', message: $count.' commentaire(s) approuvé(s).', variant: 'success');
    }

    public function bulkSpam(): void
    {
        if ($this->selected === []) {
            return;
        }
        AuthorComment::whereIn('id', $this->selected)->update(['flagged_at' => now(), 'spam_score' => 100, 'approved_at' => null]);
        Log::channel('daily')->info('authors.comment.bulk_spam', ['ids' => $this->selected, 'user_id' => auth()->id()]);
        $count = count($this->selected);
        $this->selected = [];
        $this->dispatch('toast-show', message: $count.' commentaire(s) marqué(s) comme spam.', variant: 'warning');
    }

    public function render()
    {
        $query = AuthorComment::query()
            ->with(['authorProfile.user:id,name', 'commentable']);

        if (trim($this->search) !== '') {
            $escaped = '%'.addcslashes(trim($this->search), '%_\\').'%';
            $query->where('body', 'like', $escaped);
        }

        $query = match ($this->status) {
            'pending' => $query->whereNull('approved_at')->whereNull('flagged_at')->where('spam_score', '<', 70),
            'approved' => $query->whereNotNull('approved_at'),
            'spam' => $query->where(fn ($q) => $q->whereNotNull('flagged_at')->orWhere('spam_score', '>=', 70)),
            default => $query,
        };

        $comments = $query->orderByDesc('created_at')->paginate(20);

        $counts = [
            'pending' => AuthorComment::whereNull('approved_at')->whereNull('flagged_at')->where('spam_score', '<', 70)->count(),
            'approved' => AuthorComment::whereNotNull('approved_at')->count(),
            'spam' => AuthorComment::where(fn ($q) => $q->whereNotNull('flagged_at')->orWhere('spam_score', '>=', 70))->count(),
            'all' => AuthorComment::count(),
        ];

        return view('authors::livewire.comment-moderation-queue', [
            'comments' => $comments,
            'counts' => $counts,
        ]);
    }
}
