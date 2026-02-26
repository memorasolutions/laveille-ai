<?php

declare(strict_types=1);

namespace Modules\AI\Observers;

use Illuminate\Support\Facades\Log;
use Modules\AI\Services\AiService;
use Modules\Blog\Models\Comment;
use Modules\Blog\States\ApprovedCommentState;
use Modules\Blog\States\SpamCommentState;
use Modules\Settings\Models\Setting;

class CommentModerationObserver
{
    public function created(Comment $comment): void
    {
        if (! (bool) Setting::get('ai.auto_moderation_enabled', false)) {
            return;
        }

        try {
            /** @var AiService $service */
            $service = app(AiService::class);
            $result = $service->moderateContent($comment->content);

            $threshold = (float) Setting::get('ai.moderation_threshold', '0.7');

            if ($result['confidence'] < $threshold) {
                return;
            }

            if ($result['verdict'] === 'approve') {
                $comment->status->transitionTo(ApprovedCommentState::class);
            } elseif ($result['verdict'] === 'spam') {
                $comment->status->transitionTo(SpamCommentState::class);
            }
            // 'flag' → stays pending for manual review
        } catch (\Exception $e) {
            Log::warning('AI moderation failed for comment #'.$comment->id.': '.$e->getMessage());
        }
    }
}
