<?php

declare(strict_types=1);

namespace Modules\Directory\Http\Controllers;

use App\Models\User;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Modules\Directory\Services\ReputationService;

class ProfileController extends Controller
{
    public function show(int $id): View
    {
        $user = User::findOrFail($id);
        $levelInfo = ReputationService::getLevelInfo($user->trust_level ?? 0);
        $badges = DB::table('user_badges')->where('user_id', $user->id)->pluck('badge_key')->toArray();

        $stats = [
            'reviews' => DB::table('directory_reviews')->where('user_id', $user->id)->where('is_approved', true)->count(),
            'discussions' => DB::table('directory_discussions')->where('user_id', $user->id)->where('is_approved', true)->count(),
            'resources' => DB::table('directory_resources')->where('user_id', $user->id)->where('is_approved', true)->count(),
            'suggestions' => DB::table('directory_suggestions')->where('user_id', $user->id)->where('status', 'approved')->count(),
            'likes_received' => (int) DB::table('directory_reviews')->where('user_id', $user->id)->sum('upvotes')
                + (int) DB::table('directory_discussions')->where('user_id', $user->id)->sum('upvotes')
                + (int) DB::table('directory_resources')->where('user_id', $user->id)->sum('upvotes'),
        ];

        // ACTION: critère d'activité réelle pour noindex conditionnel (audit AdSense 2026-08-20).
        // Un profil sans aucune contribution approuvée (avis/discussion/ressource) est une coquille
        // vide pour l'indexation - la donnée du membre n'est pas touchée, seule la balise robots.
        // MCP: SELF (<5 lignes, simple somme des compteurs déjà calculés)
        // RAISON: DRY avec page_noindex (cf. master.blade.php), aucune requête supplémentaire.
        $isThinProfile = ($stats['reviews'] + $stats['discussions'] + $stats['resources']) === 0;

        return view('directory::public.profile', compact('user', 'levelInfo', 'badges', 'stats', 'isThinProfile'));
    }
}
