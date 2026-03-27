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
        $this->reputation = new ReputationService();
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
        if ($review->user) {
            $this->reputation->addPoints($review->user, ReputationService::REVIEW_APPROVED, 'review_approved');
        }
        return back()->with('success', __('Avis approuvé.'));
    }

    public function rejectReview(int $id): RedirectResponse
    {
        $review = ToolReview::findOrFail($id);
        if ($review->user) {
            $this->reputation->addPoints($review->user, ReputationService::CONTENT_REJECTED, 'review_rejected');
        }
        $review->delete();
        return back()->with('success', __('Avis rejeté.'));
    }

    public function approveResource(int $id): RedirectResponse
    {
        $resource = ToolResource::findOrFail($id);
        $resource->update(['is_approved' => true]);
        if ($resource->user) {
            $this->reputation->addPoints($resource->user, ReputationService::RESOURCE_APPROVED, 'resource_approved');
        }
        return back()->with('success', __('Ressource approuvée.'));
    }

    public function rejectResource(int $id): RedirectResponse
    {
        $resource = ToolResource::findOrFail($id);
        if ($resource->user) {
            $this->reputation->addPoints($resource->user, ReputationService::CONTENT_REJECTED, 'resource_rejected');
        }
        $resource->delete();
        return back()->with('success', __('Ressource rejetée.'));
    }

    public function deleteResource(int $id): RedirectResponse
    {
        ToolResource::findOrFail($id)->delete();

        return back()->with('success', __('Ressource supprimée (sans pénalité).'));
    }

    public function resolveReport(int $id): RedirectResponse
    {
        ToolReport::findOrFail($id)->update(['is_resolved' => true]);
        return back()->with('success', __('Signalement résolu.'));
    }

    public function deleteReported(int $id): RedirectResponse
    {
        $report = ToolReport::findOrFail($id);
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

        if ($suggestion->user) {
            $suggestion->user->notify(new \Modules\Directory\Notifications\SuggestionRejectedNotification($suggestion));
        }

        return back()->with('success', __('Suggestion rejetée.'));
    }
}
