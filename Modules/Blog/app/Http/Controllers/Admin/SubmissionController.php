<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 */

declare(strict_types=1);

namespace Modules\Blog\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Modules\Blog\Models\Article;

class SubmissionController extends Controller
{
    public function index(): View
    {
        $submissions = Article::whereNotNull('submitted_by')
            ->with('submittedByUser', 'blogCategory')
            ->orderByRaw("FIELD(submission_status, 'pending', 'approved', 'rejected')")
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('blog::admin.submissions.index', compact('submissions'));
    }

    public function approve(Article $article): RedirectResponse
    {
        $article->submission_status = 'approved';
        $article->published_at = now();
        $article->save();

        // Transition d'état via Spatie ModelStates
        if (method_exists($article->status, 'transitionTo')) {
            try {
                $article->status->transitionTo(\Modules\Blog\States\PublishedArticleState::class);
            } catch (\Exception $e) {
                // Fallback si la transition n'est pas permise depuis l'état actuel
                $article->status = new \Modules\Blog\States\PublishedArticleState($article);
                $article->save();
            }
        }

        // Points de réputation + notification courriel
        if ($article->submitted_by) {
            $user = User::find($article->submitted_by);
            if ($user) {
                if (class_exists(\Modules\Directory\Services\ReputationService::class)) {
                    $reputation = new \Modules\Directory\Services\ReputationService();
                    $reputation->addPoints($user, 25, 'article_approved');
                }
                try {
                    \Illuminate\Support\Facades\Mail::to($user->email)
                        ->send(new \Modules\Blog\Mail\ArticleSubmissionNotification($article, 'approved'));
                } catch (\Exception $e) {
                    // Silencieux si le mail échoue (SMTP pas configuré en dev)
                }
            }
        }

        return redirect()->route('admin.blog.submissions.index')
            ->with('success', __('Article approuvé et publié !'));
    }

    public function reject(Article $article): RedirectResponse
    {
        $article->submission_status = 'rejected';
        $article->save();

        // Notification courriel
        if ($article->submitted_by) {
            $user = User::find($article->submitted_by);
            if ($user) {
                try {
                    \Illuminate\Support\Facades\Mail::to($user->email)
                        ->send(new \Modules\Blog\Mail\ArticleSubmissionNotification($article, 'rejected'));
                } catch (\Exception $e) {
                    // Silencieux si le mail échoue
                }
            }
        }

        return redirect()->route('admin.blog.submissions.index')
            ->with('success', __('Article refusé.'));
    }
}
