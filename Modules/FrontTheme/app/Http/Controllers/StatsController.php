<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 */

declare(strict_types=1);

namespace Modules\FrontTheme\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

/**
 * S90 #43 — Page /stats publique : compteurs temps réel pour signal d'autorité
 * (best practice 2026 EEAT + transparence type Plausible Analytics).
 */
class StatsController extends Controller
{
    public function index(): View
    {
        // #245 — TTL réduit 3600 → 300 (5 min) pour auto-refresh quasi temps réel quand on ajoute
        // contenu (outils, articles, termes glossaire). Bump clé v1 → v2 force recomputation
        // post-déploiement après fix bug glossary status → is_published.
        $stats = Cache::remember('frontstats:public:v2', 300, function (): array {
            return [
                'tools_total' => $this->countIfTable('directory_tools', fn ($q) => $q->where('status', 'published')),
                'tools_with_education' => $this->countIfTable('directory_tools', fn ($q) => $q->where('status', 'published')->where('has_education_pricing', 1)),
                'collections_public' => $this->countIfTable('tool_collections', fn ($q) => $q->where('is_public', 1)),
                'tools_with_screenshot' => $this->countIfTable('directory_tools', fn ($q) => $q->where('status', 'published')->whereNotNull('screenshot')),
                'tutorials_fr' => $this->countIfTable('directory_resources', fn ($q) => $q->where('language', 'fr')->where('is_approved', 1)),
                'tutorials_en' => $this->countIfTable('directory_resources', fn ($q) => $q->where('language', 'en')->where('is_approved', 1)),
                'tutorials_total' => $this->countIfTable('directory_resources', fn ($q) => $q->where('is_approved', 1)),
                'articles_published' => $this->countIfTable('articles', fn ($q) => $q->whereNotNull('published_at')->where('published_at', '<=', now())),
                // #248 — match case-insensitive + accent-flexible ('concentr' sans accent matche
                // "concentré"/"Concentré"/"Concentre"). Diagnostic 2026-05-19 : titres réels en DB
                // commencent par "Le concentré..." (c minuscule) → ancien LIKE %Concentré% fragile.
                'concentres_published' => $this->countIfTable('articles', fn ($q) => $q->whereNotNull('published_at')->where('published_at', '<=', now())->where(function ($qq) {
                    $qq->whereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(`title`, '$.fr_CA'))) LIKE '%concentr%'")
                       ->orWhereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(`title`, '$.fr'))) LIKE '%concentr%'");
                })),
                'glossary_terms' => $this->countIfTable('dictionary_terms', fn ($q) => $q->where('is_published', 1)),
                'acronyms' => $this->countIfTable('acronyms'),
                'interactive_tools' => $this->countIfTable('tools', fn ($q) => $q->where('is_active', 1)),
                'last_tool_added' => $this->lastDateIfTable('directory_tools', 'created_at'),
                'last_article_published' => $this->lastDateIfTable('articles', 'published_at'),
                'last_tutorial_added' => $this->lastDateIfTable('directory_resources', 'created_at'),
                'updated_at' => now()->toIso8601String(),
            ];
        });

        return view('fronttheme::stats', compact('stats'));
    }

    private function countIfTable(string $table, ?callable $query = null): int
    {
        if (! Schema::hasTable($table)) {
            return 0;
        }
        $q = DB::table($table);
        if ($query !== null) {
            $query($q);
        }
        try {
            return (int) $q->count();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    private function lastDateIfTable(string $table, string $col): ?string
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $col)) {
            return null;
        }
        try {
            $val = DB::table($table)->whereNotNull($col)->orderByDesc($col)->value($col);
            if (! $val) {
                return null;
            }
            return \Carbon\Carbon::parse($val)->toIso8601String();
        } catch (\Throwable $e) {
            return null;
        }
    }
}
