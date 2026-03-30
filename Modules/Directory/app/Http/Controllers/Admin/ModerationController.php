<?php

declare(strict_types=1);

namespace Modules\Directory\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Modules\Directory\Models\ToolReport;
use Modules\Directory\Models\ToolResource;
use Modules\Directory\Models\ToolReview;
use Modules\Directory\Services\ReputationService;

class ModerationController extends Controller
{
    private ReputationService $reputation;

    public function __construct()
    {
        $this->reputation = new ReputationService;
    }

    public function index(): View
    {
        $pendingReviews = ToolReview::where('is_approved', false)->with('user', 'tool')->latest()->get();
        $pendingResources = ToolResource::where('is_approved', false)->with('user', 'tool')->latest()->get();
        $reports = ToolReport::where('is_resolved', false)->with('user')->latest()->get();
        $pendingSuggestions = \Modules\Directory\Models\ToolSuggestion::where('status', 'pending')->with('user', 'tool')->latest()->get();

        $counts = [
            'reviews' => $pendingReviews->count(),
            'resources' => $pendingResources->count(),
            'reports' => $reports->count(),
            'suggestions' => $pendingSuggestions->count(),
        ];

        return view('directory::admin.moderation', compact('pendingReviews', 'pendingResources', 'reports', 'pendingSuggestions', 'counts'));
    }

    public function approveReview(int $id): RedirectResponse
    {
        $review = ToolReview::findOrFail($id);
        $review->update(['is_approved' => true]);
        activity('moderation')->performedOn($review)->causedBy(auth()->user())->log('review_approved');
        if ($review->user) {
            $this->reputation->addPoints($review->user, ReputationService::REVIEW_APPROVED, 'review_approved');
            $review->user->notify(new \Modules\Directory\Notifications\ReviewApprovedNotification($review));
        }

        return back()->with('success', __('Avis approuvé.'));
    }

    public function rejectReview(int $id): RedirectResponse
    {
        $review = ToolReview::with('tool')->findOrFail($id);
        activity('moderation')->performedOn($review)->causedBy(auth()->user())->log('review_rejected');
        if ($review->user) {
            $this->reputation->addPoints($review->user, ReputationService::CONTENT_REJECTED, 'review_rejected');
            $review->user->notify(new \Modules\Directory\Notifications\ReviewRejectedNotification($review));
        }
        $review->delete();

        return back()->with('success', __('Avis rejeté.'));
    }

    public function approveResource(int $id): RedirectResponse
    {
        $resource = ToolResource::findOrFail($id);
        $resource->update(['is_approved' => true]);
        activity('moderation')->performedOn($resource)->causedBy(auth()->user())->log('resource_approved');
        if ($resource->user) {
            $this->reputation->addPoints($resource->user, ReputationService::RESOURCE_APPROVED, 'resource_approved');
            $resource->user->notify(new \Modules\Directory\Notifications\ResourceApprovedNotification($resource));
        }

        return back()->with('success', __('Ressource approuvée.'));
    }

    public function rejectResource(int $id): RedirectResponse
    {
        $resource = ToolResource::with('tool')->findOrFail($id);
        activity('moderation')->performedOn($resource)->causedBy(auth()->user())->log('resource_rejected');
        if ($resource->user) {
            $this->reputation->addPoints($resource->user, ReputationService::CONTENT_REJECTED, 'resource_rejected');
            $resource->user->notify(new \Modules\Directory\Notifications\ResourceRejectedNotification($resource));
        }
        $resource->delete();

        return back()->with('success', __('Ressource rejetée.'));
    }

    public function deleteResource(int $id): RedirectResponse
    {
        $resource = ToolResource::findOrFail($id);
        activity('moderation')->performedOn($resource)->causedBy(auth()->user())->log('resource_deleted');
        $resource->delete();

        return back()->with('success', __('Ressource supprimée (sans pénalité).'));
    }

    public function resources(): View
    {
        $resources = ToolResource::with('user', 'tool')->latest()->paginate(20);

        return view('directory::admin.resources', compact('resources'));
    }

    public function editResource(int $id): View
    {
        $resource = ToolResource::with(['tool', 'user'])->findOrFail($id);

        return view('directory::admin.resource-edit', compact('resource'));
    }

    public function updateResource(\Illuminate\Http\Request $request, int $id): RedirectResponse
    {
        $resource = ToolResource::findOrFail($id);

        $resource->update($request->validate([
            'title' => 'required|string|max:255',
            'type' => 'required|in:video,article,tutorial,documentation',
            'language' => 'required|in:fr,en',
            'video_summary' => 'nullable|string|max:5000',
            'duration_seconds' => 'nullable|integer|min:0',
            'channel_name' => 'nullable|string|max:255',
            'channel_url' => 'nullable|url|max:500',
            'is_approved' => 'nullable|boolean',
        ]));

        $resource->is_approved = $request->boolean('is_approved');
        $resource->save();

        return redirect()->route('admin.directory.resources')->with('success', __('Ressource mise à jour.'));
    }

    public function resolveReport(int $id): RedirectResponse
    {
        $report = ToolReport::findOrFail($id);
        $report->update(['is_resolved' => true]);
        activity('moderation')->performedOn($report)->causedBy(auth()->user())->log('report_resolved');

        return back()->with('success', __('Signalement résolu.'));
    }

    public function deleteReported(int $id): RedirectResponse
    {
        $report = ToolReport::findOrFail($id);
        activity('moderation')->performedOn($report)->causedBy(auth()->user())->withProperties(['reportable_type' => $report->reportable_type, 'reportable_id' => $report->reportable_id])->log('reported_content_deleted');
        $reportableClass = $report->reportable_type;
        if (class_exists($reportableClass)) {
            $reportableClass::where('id', $report->reportable_id)->delete();
        }
        $report->delete();

        return back()->with('success', __('Contenu et signalement supprimés.'));
    }

    public function approveSuggestion(int $id): RedirectResponse
    {
        $suggestion = \Modules\Directory\Models\ToolSuggestion::findOrFail($id);
        $suggestion->update(['status' => 'approved']);
        activity('moderation')->performedOn($suggestion)->causedBy(auth()->user())->log('suggestion_approved');

        // Appliquer la modification sur le modèle (polymorphe : Tool, Term, Acronym)
        $model = $suggestion->suggestable ?? $suggestion->tool;
        if ($model && $suggestion->field !== 'other' && in_array($suggestion->field, $model->getFillable())) {
            if (method_exists($model, 'setTranslation')) {
                $model->setTranslation($suggestion->field, app()->getLocale(), $suggestion->suggested_value);
            } else {
                $model->{$suggestion->field} = $suggestion->suggested_value;
            }
            $model->save();
        }

        if ($suggestion->user) {
            $this->reputation->addPoints($suggestion->user, 5, 'suggestion_approved');
            $suggestion->user->notify(new \Modules\Directory\Notifications\SuggestionApprovedNotification($suggestion));
        }

        return back()->with('success', __('Suggestion approuvée et appliquée.'));
    }

    public function rejectSuggestion(int $id): RedirectResponse
    {
        $suggestion = \Modules\Directory\Models\ToolSuggestion::findOrFail($id);
        $suggestion->update(['status' => 'rejected']);
        activity('moderation')->performedOn($suggestion)->causedBy(auth()->user())->log('suggestion_rejected');

        if ($suggestion->user) {
            $suggestion->user->notify(new \Modules\Directory\Notifications\SuggestionRejectedNotification($suggestion));
        }

        return back()->with('success', __('Suggestion rejetée.'));
    }
}
