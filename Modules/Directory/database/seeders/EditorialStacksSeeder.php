<?php

declare(strict_types=1);

namespace Modules\Directory\Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * S90 #43 — 8 Stacks IA recommandés par persona (best practice 2026 long-tail SEO).
 *
 * Différence vs collections "top par tâche" : un STACK est un workflow combiné
 * — outils complémentaires utilisés ensemble pour un profil utilisateur précis.
 *
 * URL : /collections/stack-{slug}. Index : /collections.
 * Idempotent (skip si slug existe).
 */
class EditorialStacksSeeder extends Seeder
{
    public function run(): void
    {
        $stacks = [
            [
                'slug' => 'stack-enseignant-primaire-quebec',
                'name' => 'Stack IA — Enseignant primaire Québec',
                'description' => "Combinaison d'outils IA recommandée pour un enseignant du primaire au Québec en 2026 : préparation de cours différenciée, gestion de classe, lecture, communication parents-élèves. Tous testés et compatibles Loi 25.",
                'tool_slugs' => ['magicschool-ai', 'diffit', 'canva-ai', 'edpuzzle', 'classdojo', 'alloprof'],
            ],
            [
                'slug' => 'stack-freelance-createur-2026',
                'name' => 'Stack IA — Freelance créateur (designer, vidéaste, rédacteur)',
                'description' => "L'arsenal complet du créateur indépendant en 2026 : génération d'images, présentations, vidéos, captations vocales et organisation. Mix freemium pour budget startup.",
                'tool_slugs' => ['midjourney', 'canva-ai', 'adobe-firefly', 'loom', 'notion-ai', 'otter-ai'],
            ],
            [
                'slug' => 'stack-startup-saas-quebec',
                'name' => 'Stack IA — Startup SaaS Québec',
                'description' => "L'IA pour bâtir et lancer un SaaS au Québec en 2026 : code, support, marketing, traduction FR-EN. Compromis qualité/coût optimisé pour bootstrap.",
                'tool_slugs' => ['claude', 'github-copilot', 'notion-ai', 'loom', 'mistral', 'deepl'],
            ],
            [
                'slug' => 'stack-etudiant-universitaire-quebec',
                'name' => 'Stack IA — Étudiant universitaire',
                'description' => "Les outils IA pour réussir en 2026 à l'université : recherche, prise de notes, révision, rédaction, mathématiques. Tous gratuits ou avec programme étudiant officiel.",
                'tool_slugs' => ['notebooklm', 'perplexity', 'claude', 'quizlet', 'wolfram-alpha', 'grammarly'],
            ],
            [
                'slug' => 'stack-marketeur-pme-quebec',
                'name' => 'Stack IA — Marketeur PME québécoise',
                'description' => "L'IA pour propulser le marketing d'une PME au Québec : copywriting bilingue, visuels, traduction FR/EN, analyse comportement. Approche pragmatique 2026.",
                'tool_slugs' => ['jasper-ai', 'copyai', 'canva-ai', 'deepl', 'crazzy', 'otter-ai'],
            ],
            [
                'slug' => 'stack-recherche-academique-2026',
                'name' => 'Stack IA — Chercheur·euse académique',
                'description' => "L'arsenal IA pour la recherche académique en 2026 : revue de littérature, organisation des sources, rédaction d'articles, traduction multilingue.",
                'tool_slugs' => ['perplexity', 'semantic-scholar', 'notebooklm', 'claude', 'deepl', 'grammarly'],
            ],
            [
                'slug' => 'stack-developpeur-full-stack-2026',
                'name' => 'Stack IA — Développeur full-stack',
                'description' => "Le poste de travail IA du développeur en 2026 : autocomplétion, pair-programming, debug, revues de code. Mix ouvert (Aider, Codeium) et propriétaire (Copilot, Cursor).",
                'tool_slugs' => ['github-copilot', 'cursor', 'aider', 'codeium', 'claude', 'tabnine'],
            ],
            [
                'slug' => 'stack-conferencier-formateur',
                'name' => 'Stack IA — Conférencier·ère / formateur·trice',
                'description' => "Préparer, animer et mesurer ses conférences ou formations en 2026 : présentations IA, sondages live, captation et résumé automatique.",
                'tool_slugs' => ['gamma', 'tome', 'mentimeter', 'otter-ai', 'loom', 'pear-deck'],
            ],
        ];

        $now = Carbon::now();
        $adminUserId = (int) (DB::table('users')->orderBy('id')->value('id') ?? 1);
        $slugExpr = "JSON_UNQUOTE(JSON_EXTRACT(slug, '$.\"fr_CA\"'))";

        foreach ($stacks as $s) {
            $existing = DB::table('tool_collections')->where('slug', $s['slug'])->first();
            if ($existing) {
                $this->command?->info("[SKIP] Stack {$s['slug']} déjà existant (id={$existing->id})");
                continue;
            }

            $colId = DB::table('tool_collections')->insertGetId([
                'user_id' => $adminUserId,
                'name' => $s['name'],
                'slug' => $s['slug'],
                'description' => $s['description'],
                'is_public' => 1,
                'position' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $position = 0;
            $linked = 0;
            foreach ($s['tool_slugs'] as $slug) {
                $toolId = DB::table('directory_tools')
                    ->whereRaw("$slugExpr = ?", [$slug])
                    ->where('status', 'published')
                    ->value('id');

                if (!$toolId) {
                    continue;
                }

                DB::table('tool_collection_items')->insertOrIgnore([
                    'collection_id' => $colId,
                    'tool_id' => (int) $toolId,
                    'position' => $position,
                    'added_at' => $now,
                ]);
                $position++;
                $linked++;
            }

            $this->command?->info("[OK] Stack {$s['slug']} créé (id=$colId, $linked/" . count($s['tool_slugs']) . ' outils liés)');
        }
    }
}
