<?php

declare(strict_types=1);

namespace Modules\Directory\Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeder éditorial S90 #43 — 10 collections "Top outils par tâche" pour SEO long-tail.
 *
 * Best practice 2026 : pages task-based dominent les SERP francophones IA
 * (G2, Capterra, Futurepedia). Idempotent : skip si slug existe.
 *
 * URL pattern : /annuaire/collections/{slug}
 * Index : /annuaire/collections
 */
class EditorialCollectionsSeeder extends Seeder
{
    public function run(): void
    {
        $collections = [
            [
                'slug' => 'top-outils-ia-enseignants-quebec',
                'name' => 'Top outils IA pour enseignants au Québec',
                'description' => "Sélection éditoriale 2026 des outils d'IA les plus utiles aux enseignants du Québec et de la francophonie : LMS, IA pédagogique, quiz interactifs, accessibilité. Tous testés, vérifiés conformes Loi 25 ou avec programme éducation officiel.",
                'tool_slugs' => ['google-classroom', 'kahoot', 'magicschool-ai', 'curipod', 'wooclap', 'alloprof', 'genially', 'edpuzzle', 'padlet', 'diffit'],
            ],
            [
                'slug' => 'meilleurs-outils-ia-redaction-francais',
                'name' => 'Meilleurs outils IA pour rédiger en français',
                'description' => "Outils d'IA générative qui maîtrisent vraiment le français (FR-CA et FR-FR) pour la rédaction d'articles, courriels, résumés et contenus pédagogiques. Sélection éditoriale 2026.",
                'tool_slugs' => ['chatgpt', 'claude', 'gemini', 'perplexity', 'copilot', 'mistral', 'grammarly', 'wordtune'],
            ],
            [
                'slug' => 'ia-creer-presentations-pedagogiques',
                'name' => 'IA pour créer des présentations pédagogiques',
                'description' => "Outils d'IA qui génèrent automatiquement des présentations attractives à partir d'un sujet ou d'un brouillon. Idéal pour enseignants, formateurs et conférenciers francophones.",
                'tool_slugs' => ['gamma', 'canva-ai', 'beautifulai', 'pear-deck', 'genially', 'tome', 'decktopus'],
            ],
            [
                'slug' => 'outils-ia-gratuits-etudiants',
                'name' => 'Outils IA gratuits pour étudiants',
                'description' => "Sélection vérifiée 2026 d'outils d'IA gratuits ou avec programme éducation officiel pour étudiants : recherche, rédaction, codage, organisation. Tous offrent un accès sans frais aux étudiants.",
                'tool_slugs' => ['notebooklm', 'perplexity', 'chatgpt', 'github-copilot', 'notion-ai', 'canva-ai', 'gemini', 'grammarly', 'codeium'],
            ],
            [
                'slug' => 'quiz-evaluation-interactifs-classe',
                'name' => 'Quiz et évaluation interactifs pour la classe',
                'description' => "Plateformes pour créer des quiz, sondages et évaluations formatives en classe. Toutes fonctionnent en français et sont compatibles K-12 + supérieur.",
                'tool_slugs' => ['kahoot', 'quizizz', 'quizlet', 'mentimeter', 'wooclap', 'socrative', 'pear-deck', 'nearpod'],
            ],
            [
                'slug' => 'ia-marketing-startup-quebec',
                'name' => 'IA pour le marketing et les startups au Québec',
                'description' => "Outils d'IA pour startup québécoise : copywriting, génération d'images, automatisation marketing, analyse de données. Sélection 2026 axée productivité et coût.",
                'tool_slugs' => ['chatgpt', 'claude', 'canva-ai', 'jasper-ai', 'copyai', 'midjourney', 'crazzy', 'invideo'],
            ],
            [
                'slug' => 'traduction-langues-ia',
                'name' => 'Outils de traduction IA en français',
                'description' => "Les meilleurs outils de traduction automatique compatibles français (FR-CA et FR-FR) : DeepL, Google Translate avec IA, et alternatives spécialisées contexte/ton.",
                'tool_slugs' => ['deepl', 'chatgpt', 'gemini', 'claude', 'reverso'],
            ],
            [
                'slug' => 'ia-developpeurs-code',
                'name' => 'IA pour développeurs et code',
                'description' => "Assistants IA pour coder plus vite et mieux : autocomplétion intelligente, génération de fonctions, refactoring, debug, revues. Sélection 2026 pour devs francophones.",
                'tool_slugs' => ['github-copilot', 'codeium', 'claude', 'chatgpt', 'cursor', 'tabnine', 'replit', 'aider'],
            ],
            [
                'slug' => 'ia-creation-images-illustrations',
                'name' => 'IA pour créer images et illustrations',
                'description' => "Outils d'IA générative pour créer des images, illustrations et visuels pédagogiques. Sélection 2026 incluant des options gratuites pour enseignants et créateurs francophones.",
                'tool_slugs' => ['midjourney', 'adobe-firefly', 'canva-ai', 'stable-diffusion', 'dall-e', 'leonardo-ai', 'ideogram', 'flux-ai'],
            ],
            [
                'slug' => 'ia-prise-notes-reunions',
                'name' => 'IA pour la prise de notes et résumé de réunions',
                'description' => "Outils d'IA qui transcrivent, résument et organisent les réunions vidéo (Zoom, Teams, Google Meet) avec support du français. Gain de temps massif pour gestionnaires et profs.",
                'tool_slugs' => ['otter-ai', 'fireflies', 'notebooklm', 'tldv', 'fathom-video'],
            ],
        ];

        $now = Carbon::now();
        $adminUserId = (int) (DB::table('users')->orderBy('id')->value('id') ?? 1);

        foreach ($collections as $c) {
            // Idempotent : skip si slug existe
            $existing = DB::table('tool_collections')->where('slug', $c['slug'])->first();
            if ($existing) {
                $this->command?->info("[SKIP] Collection {$c['slug']} déjà existante (id={$existing->id})");
                continue;
            }

            $collectionId = DB::table('tool_collections')->insertGetId([
                'user_id' => $adminUserId,
                'name' => $c['name'],
                'slug' => $c['slug'],
                'description' => $c['description'],
                'is_public' => 1,
                'position' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $slugExpr = "JSON_UNQUOTE(JSON_EXTRACT(slug, '$.\"fr_CA\"'))";
            $position = 0;
            $added = 0;
            foreach ($c['tool_slugs'] as $slug) {
                $toolId = DB::table('directory_tools')
                    ->whereRaw("$slugExpr = ?", [$slug])
                    ->where('status', 'published')
                    ->value('id');

                if (!$toolId) {
                    continue;
                }

                DB::table('tool_collection_items')->insertOrIgnore([
                    'collection_id' => $collectionId,
                    'tool_id' => (int) $toolId,
                    'position' => $position,
                    'added_at' => $now,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                $position++;
                $added++;
            }

            $this->command?->info("[OK] Collection {$c['slug']} créée (id=$collectionId, $added/" . count($c['tool_slugs']) . ' outils liés)');
        }
    }
}
