<?php

declare(strict_types=1);

namespace Modules\Directory\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Modules\Directory\Models\Tool;
use Modules\Directory\Models\ToolDiscussion;
use Modules\Directory\Models\ToolReport;
use Modules\Directory\Models\ToolResource;
use Modules\Directory\Models\ToolReview;
use Modules\Directory\Services\ReputationService;

class CommunityController extends Controller
{
    private ReputationService $reputation;

    public function __construct()
    {
        $this->reputation = new ReputationService();
    }

    private function findTool(string $slug): Tool
    {
        return Tool::published()
            ->where('slug->' . app()->getLocale(), $slug)
            ->firstOrFail();
    }

    public function storeReview(Request $request, string $slug): RedirectResponse
    {
        $tool = $this->findTool($slug);

        $validated = $request->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'title' => ['required', 'string', 'max:255'],
            'pros' => ['nullable', 'string', 'max:1000'],
            'cons' => ['nullable', 'string', 'max:1000'],
            'body' => ['required', 'string', 'max:2000'],
        ]);

        $user = Auth::user();
        $autoApprove = $this->reputation->shouldAutoApprove($user, 'review');

        ToolReview::create([
            'user_id' => $user->id,
            'directory_tool_id' => $tool->id,
            'rating' => $validated['rating'],
            'title' => $validated['title'],
            'pros' => $validated['pros'] ?? null,
            'cons' => $validated['cons'] ?? null,
            'body' => $validated['body'],
            'is_approved' => $autoApprove,
        ]);

        if ($autoApprove) {
            $this->reputation->addPoints($user, ReputationService::REVIEW_APPROVED, 'review_auto');
            return back()->with('success', __('Votre avis a été publié automatiquement !'));
        }

        return back()->with('success', __('Merci pour votre avis ! Il sera visible après approbation.'));
    }

    public function storeDiscussion(Request $request, string $slug): RedirectResponse
    {
        $tool = $this->findTool($slug);

        $validated = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:2000'],
            'parent_id' => ['nullable', 'exists:directory_discussions,id'],
        ]);

        $user = Auth::user();
        $isReply = ! empty($validated['parent_id']);
        $autoApprove = $isReply ? true : $this->reputation->shouldAutoApprove($user, 'discussion');

        ToolDiscussion::create([
            'user_id' => $user->id,
            'directory_tool_id' => $tool->id,
            'parent_id' => $validated['parent_id'] ?? null,
            'title' => $validated['title'] ?? null,
            'body' => $validated['body'],
            'is_approved' => $autoApprove,
        ]);

        $pts = $isReply ? ReputationService::REPLY_APPROVED : ReputationService::DISCUSSION_APPROVED;
        if ($autoApprove) {
            $this->reputation->addPoints($user, $pts, $isReply ? 'reply' : 'discussion');
        }

        return back()->with('success', __('Votre message a été publié.'));
    }

    public function storeResource(Request $request, string $slug): RedirectResponse
    {
        $tool = $this->findTool($slug);

        $validated = $request->validate([
            'url' => ['required', 'url', 'max:500'],
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:video,article,tutorial,documentation'],
            'language' => ['required', 'in:fr,en'],
        ]);

        $user = Auth::user();
        $autoApprove = $this->reputation->shouldAutoApprove($user, 'resource');

        ToolResource::create([
            'user_id' => $user->id,
            'directory_tool_id' => $tool->id,
            'url' => $validated['url'],
            'title' => $validated['title'],
            'type' => $validated['type'],
            'language' => $validated['language'],
            'is_approved' => $autoApprove,
        ]);

        if ($autoApprove) {
            $this->reputation->addPoints($user, ReputationService::RESOURCE_APPROVED, 'resource_auto');
        }

        return back()->with('success', __('Merci ! La ressource sera visible après approbation.'));
    }

    public function toggleLike(Request $request, string $type, int $id): JsonResponse
    {
        $model = match ($type) {
            'review' => ToolReview::findOrFail($id),
            'discussion' => ToolDiscussion::findOrFail($id),
            'resource' => ToolResource::findOrFail($id),
            default => abort(404),
        };

        $model->increment('upvotes');

        // Donner un point de réputation à l'auteur du contenu liké
        if (isset($model->user_id) && $model->user_id) {
            $author = \App\Models\User::find($model->user_id);
            if ($author) {
                $this->reputation->addPoints($author, ReputationService::LIKE_RECEIVED, 'like_received');
            }
        }

        return response()->json(['upvotes' => $model->fresh()->upvotes]);
    }

    public function report(Request $request, string $type, int $id): RedirectResponse
    {
        $modelClass = match ($type) {
            'review' => ToolReview::class,
            'discussion' => ToolDiscussion::class,
            'resource' => ToolResource::class,
            default => abort(404),
        };

        $modelClass::findOrFail($id);

        $validated = $request->validate([
            'reason' => ['required', 'in:spam,inappropriate,off_topic,other'],
            'comment' => ['nullable', 'string', 'max:500'],
        ]);

        ToolReport::create([
            'user_id' => Auth::id(),
            'reportable_type' => $modelClass,
            'reportable_id' => $id,
            'reason' => $validated['reason'],
            'comment' => $validated['comment'] ?? null,
        ]);

        return back()->with('success', __('Merci, le signalement a été envoyé.'));
    }

    public function storeSuggestion(Request $request, string $slug): RedirectResponse
    {
        $tool = $this->findTool($slug);

        $validated = $request->validate([
            'field' => ['required', 'in:description,short_description,pricing,url,core_features,how_to_use,use_cases,other'],
            'suggested_value' => ['required', 'string', 'max:2000'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        \Modules\Directory\Models\ToolSuggestion::create([
            'user_id' => Auth::id(),
            'directory_tool_id' => $tool->id,
            'suggestable_type' => \Modules\Directory\Models\Tool::class,
            'suggestable_id' => $tool->id,
            'field' => $validated['field'],
            'current_value' => $tool->{$validated['field']} ?? null,
            'suggested_value' => $validated['suggested_value'],
            'reason' => $validated['reason'] ?? null,
            'status' => 'pending',
        ]);

        return back()->with('success', __('Merci ! Votre suggestion sera examinée par notre équipe.'));
    }
}
