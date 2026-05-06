<?php

declare(strict_types=1);

namespace Modules\Community\Livewire;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Livewire\Component;
use Modules\Community\Models\Comment;
use Modules\Community\Notifications\NewCommentPendingNotification;

class CommentsThread extends Component
{
    public string $commentableType;

    public int $commentableId;

    public string $newComment = '';

    public ?int $replyingTo = null;

    public string $guestName = '';

    public bool $showSuccess = false;

    public string $successMessage = '';

    public function mount(string $commentableType, int $commentableId): void
    {
        $this->commentableType = $commentableType;
        $this->commentableId = $commentableId;
    }

    public function getCommentsProperty()
    {
        return Comment::with(['user', 'children.user'])
            ->where('commentable_type', $this->commentableType)
            ->where('commentable_id', $this->commentableId)
            ->whereNull('parent_id')
            ->approved()
            ->orderByDesc('created_at')
            ->get();
    }

    public function addComment(): void
    {
        $rules = ['newComment' => 'required|min:3|max:2000'];
        if (! Auth::check()) {
            $rules['guestName'] = 'required|min:2|max:255';
        }
        $this->validate($rules);

        // #192 sanitization : strip tags HTML + flag liens externes
        // Retire tous tags HTML (allowlist vide : ne garde QUE texte plain).
        $cleanContent = trim(strip_tags($this->newComment));
        // Detection URLs : http(s), www, ftp, ou domaines courts
        $hasLinks = (bool) preg_match('#(?:https?://|ftp://|www\.|\b[a-z0-9-]+\.(?:com|fr|ca|io|net|org|co|ai)\b)#i', $cleanContent);

        $comment = Comment::create([
            'commentable_type' => $this->commentableType,
            'commentable_id' => $this->commentableId,
            'user_id' => Auth::id(),
            'guest_name' => Auth::check() ? null : $this->guestName,
            'content' => $cleanContent,
            // #192 : si lien detecte, force pending meme pour user authentifie
            // (modération preventive anti-spam).
            'status' => (Auth::check() && ! $hasLinks) ? 'approved' : 'pending',
            'parent_id' => $this->replyingTo,
        ]);

        // #186 : notif email admin sur nouveau commentaire pending (visiteur).
        // Pas de notif pour commentaires user authentifies (status=approved).
        if ($comment->status === 'pending') {
            $adminEmail = config('app.superadmin_email');
            if ($adminEmail) {
                try {
                    Notification::route('mail', $adminEmail)
                        ->notify(new NewCommentPendingNotification($comment));
                } catch (\Throwable $e) {
                    Log::error('NewCommentPendingNotification fail: '.$e->getMessage(), ['comment_id' => $comment->id]);
                }
            }
        }

        $this->reset('newComment', 'guestName', 'replyingTo');

        // #165 fix : feedback visiteur (sinon "rien ne se produit" perçu)
        $this->showSuccess = true;
        $this->successMessage = Auth::check()
            ? __('Commentaire publié.')
            : __('Merci ! Votre commentaire est en attente de modération.');
    }

    public function reply(int $parentId): void
    {
        $this->replyingTo = $parentId;
    }

    public function cancelReply(): void
    {
        $this->replyingTo = null;
    }

    public function deleteComment(int $id): void
    {
        $comment = Comment::find($id);
        if ($comment && Auth::check() && $comment->user_id === Auth::id()) {
            $comment->update(['status' => 'rejected']);
        }
    }

    public function render()
    {
        return view('community::livewire.comments-thread');
    }
}
