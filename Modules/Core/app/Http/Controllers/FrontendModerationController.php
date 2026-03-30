<?php

namespace Modules\Core\Http\Controllers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\Models\Activity;

class FrontendModerationController extends Controller
{
    private function resolveModel(string $type): string
    {
        $modelMap = [
            'reviews' => \Modules\Community\Models\Review::class,
            'discussions' => \Modules\Directory\Models\ToolDiscussion::class,
            'resources' => \Modules\Directory\Models\ToolResource::class,
            'suggestions' => \Modules\Directory\Models\ToolSuggestion::class,
            'reports' => \Modules\Community\Models\Report::class,
            'acronyms' => \Modules\Acronyms\Models\Acronym::class,
        ];

        abort_unless(isset($modelMap[$type]), 404, 'Type de contenu inconnu.');

        $modelClass = $modelMap[$type];
        abort_unless(class_exists($modelClass), 404, "Module pour '{$type}' non activé.");

        return $modelClass;
    }

    private function findItem(string $type, int $id): Model
    {
        $modelClass = $this->resolveModel($type);

        return $modelClass::findOrFail($id);
    }

    public function approve(string $type, int $id)
    {
        abort_unless(Auth::user()?->can('moderate_' . $type), 403);

        $item = $this->findItem($type, $id);
        abort_unless(method_exists($item, 'approve'), 400, 'Action non supportée.');

        $item->approve();

        return redirect()->back()->with('success', __('Contenu approuvé.'));
    }

    public function reject(Request $request, string $type, int $id)
    {
        abort_unless(Auth::user()?->can('moderate_' . $type), 403);

        $item = $this->findItem($type, $id);
        abort_unless(method_exists($item, 'reject'), 400, 'Action non supportée.');

        $item->reject($request->input('reason'));

        return redirect()->back()->with('success', __('Contenu rejeté.'));
    }

    public function pin(string $type, int $id)
    {
        abort_unless(Auth::user()?->can('moderate_' . $type), 403);

        $item = $this->findItem($type, $id);
        abort_unless(method_exists($item, 'pin'), 400, 'Action non supportée.');

        $item->pin();

        return redirect()->back()->with('success', __('Contenu épinglé/désépinglé.'));
    }

    public function destroy(string $type, int $id)
    {
        abort_unless(Auth::user()?->can('moderate_' . $type), 403);

        $item = $this->findItem($type, $id);
        abort_unless(method_exists($item, 'softDeleteModerated'), 400, 'Action non supportée.');

        $item->softDeleteModerated();

        return redirect()->back()->with('success', __('Contenu supprimé.'));
    }

    public function history(string $type, int $id)
    {
        abort_unless(Auth::user()?->can('view_moderation_history'), 403);

        $item = $this->findItem($type, $id);

        $activities = Activity::where('subject_type', get_class($item))
            ->where('subject_id', $id)
            ->with('causer:id,name')
            ->latest()
            ->limit(50)
            ->get()
            ->map(fn ($a) => [
                'user' => $a->causer?->name ?? __('Système'),
                'action' => $a->description,
                'date' => $a->created_at->diffForHumans(),
                'properties' => $a->properties->toArray(),
            ]);

        return response()->json($activities);
    }

    public function banUser(Request $request, int $userId)
    {
        abort_unless(Auth::user()?->can('ban_users'), 403);

        $duration = (int) $request->input('duration', 7);
        $user = \App\Models\User::findOrFail($userId);

        $user->ban_expires_at = Carbon::now()->addDays($duration);
        $user->save();

        activity()
            ->performedOn($user)
            ->causedBy(Auth::user())
            ->withProperties(['duration_days' => $duration, 'expires_at' => $user->ban_expires_at])
            ->log('banned');

        return redirect()->back()->with('success', __('Utilisateur banni pour :days jours.', ['days' => $duration]));
    }
}
