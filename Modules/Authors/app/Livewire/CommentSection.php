<?php

declare(strict_types=1);

namespace Modules\Authors\Livewire;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Livewire\Component;
use Livewire\WithPagination;
use Modules\Authors\Mail\CommentReplyNotificationMail;
use Modules\Authors\Models\AuthorComment;
use Modules\Authors\Services\SpamDetectionService;

class CommentSection extends Component
{
    use WithPagination;

    public string $commentableType;

    public int $commentableId;

    public int $authorProfileId;

    public ?int $replyingTo = null;

    public string $body = '';

    public string $guestName = '';

    public string $guestEmail = '';

    public string $website = '';

    public bool $consent = false;

    public function mount(string $commentableType, int $commentableId, int $authorProfileId): void
    {
        $this->commentableType = $commentableType;
        $this->commentableId = $commentableId;
        $this->authorProfileId = $authorProfileId;
    }

    public function startReply(int $parentId): void
    {
        $this->replyingTo = $parentId;
    }

    public function cancelReply(): void
    {
        $this->replyingTo = null;
        $this->reset(['body']);
    }

    public function submit(SpamDetectionService $spam, Request $request): void
    {
        if (! empty($this->website)) {
            $this->reset(['body', 'guestName', 'guestEmail', 'consent']);

            return;
        }

        $this->validate();

        $user = Auth::user();
        $spamScore = $spam->score(
            $this->body,
            $user?->email ?? $this->guestEmail,
            $request->ip(),
            $request->userAgent()
        );

        $autoApprove = $user !== null && $spamScore < 30;

        $comment = AuthorComment::create([
            'author_profile_id' => $this->authorProfileId,
            'commentable_id' => $this->commentableId,
            'commentable_type' => $this->commentableType,
            'parent_id' => $this->replyingTo,
            'user_id' => $user?->id,
            'author_name' => $user?->name ?? $this->guestName,
            'author_email' => $user?->email ?? $this->guestEmail,
            'body' => $this->body,
            'reactions' => [],
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 512),
            'spam_score' => $spamScore,
            'approved_at' => $autoApprove ? now() : null,
            'flagged_at' => $spamScore >= 70 ? now() : null,
        ]);

        if ($comment->parent_id !== null) {
            $parent = AuthorComment::find($comment->parent_id);
            if ($parent && $parent->author_email) {
                Mail::to($parent->author_email)->queue(new CommentReplyNotificationMail($comment, $parent));
            }
        }

        $this->dispatch('comment-submitted', autoApproved: $autoApprove);
        $this->reset(['body', 'guestName', 'guestEmail', 'consent', 'replyingTo', 'website']);
    }

    public function toggleReaction(int $commentId, string $emoji): void
    {
        if (! in_array($emoji, AuthorComment::ALLOWED_REACTIONS, true)) {
            return;
        }

        $userId = Auth::id() ?? 'guest_'.request()->ip();
        $comment = AuthorComment::findOrFail($commentId);
        $reactions = $comment->reactions ?? [];

        $reactions[$emoji] = $reactions[$emoji] ?? [];

        if (in_array($userId, $reactions[$emoji], true)) {
            $reactions[$emoji] = array_values(array_diff($reactions[$emoji], [$userId]));
        } else {
            $reactions[$emoji][] = $userId;
        }

        if (empty($reactions[$emoji])) {
            unset($reactions[$emoji]);
        }

        $comment->reactions = $reactions;
        $comment->save();
    }

    public function render()
    {
        $comments = AuthorComment::where('commentable_type', $this->commentableType)
            ->where('commentable_id', $this->commentableId)
            ->approved()
            ->topLevel()
            ->with(['children' => fn ($q) => $q->approved()->orderBy('created_at')])
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('authors::livewire.comment-section', compact('comments'));
    }

    protected function rules(): array
    {
        $rules = [
            'body' => 'required|string|min:3|max:5000',
            'consent' => 'accepted',
        ];

        if (! Auth::check()) {
            $rules['guestName'] = 'required|string|max:80';
            $rules['guestEmail'] = 'required|email|max:255';
        }

        return $rules;
    }
}
