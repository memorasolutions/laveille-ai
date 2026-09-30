<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * Fiche annuaire LucidNest (application web d'organisation et de gestion de projets pensée pour les
 * personnes TDAH, éditée par MEMORA solutions), placée EN PREMIER dans « En vedette ». Les vedettes
 * existantes sont décalées d'un rang (featured_order + 1) pour lui laisser la position 0.
 * Idempotente : ne fait rien si une fiche correspondante existe déjà. Réversible : down() supprime
 * la fiche et son lien de catégorie, puis rétablit les rangs des vedettes.
 */
return new class extends Migration
{
    private const URL = 'https://lucidnest.io';

    public function up(): void
    {
        // Fiche de production : jamais semée dans la base des tests. Les tests du module
        // parcourent tout le catalogue de fiches (ex. dispatch-margin-recapture, qui vérifie
        // des invocations de processus exactes) et une fiche réelle en base fausse leurs
        // comptages. La protection de l'image en production ne dépend PAS de ce garde : c'est
        // screenshot_locked, posé ci-dessous, qui empêche la recapture nocturne d'écraser le
        // visuel.
        if (app()->runningUnitTests()) {
            return;
        }

        $exists = DB::table('directory_tools')
            ->where('url', 'like', '%lucidnest.io%')
            ->exists();

        if ($exists) {
            return;
        }

        $now = now();

        $description = "## LucidNest, l'organisation pensée pour les cerveaux qui débordent\n\n"
            ."LucidNest est une application web d'organisation et de gestion de projets conçue d'abord pour les personnes TDAH, et utile à tout cerveau qui pense vite. Son principe tient en une idée : réduire la charge mentale. Tu captures une pensée en une seconde dans la « Boîte de capture », sans avoir à la classer ; l'outil t'aide ensuite à la retrouver et à la transformer en tâche, note ou projet, à ton rythme.\n\n"
            ."Autour de cette capture : un minuteur de concentration, des réglages d'énergie (normal, hyperfocus, basse énergie), une carte visuelle pour penser en réseau, des tâches à priorités souples avec suivi du temps, des notes et carnets qui se retrouvent (un courriel transféré devient une note), des espaces séparés pour cloisonner tes vies, et un résumé de semaine sans gamification punitive. Et parce que tes idées doivent te suivre partout, une API REST pour développeurs (clés API, documentation sur /docs) et un serveur MCP rendent tes notes accessibles depuis tes propres outils et depuis ton assistant IA préféré, comme Claude ou ChatGPT.\n\n"
            ."**Un palier gratuit vraiment généreux** : 10 projets, 15 cahiers, notes et tâches illimitées, sans carte de crédit et sans date d'expiration. Ce n'est pas un essai : c'est un vrai forfait gratuit, et on passe au payant seulement quand on le veut.\n\n"
            ."LucidNest est conçu et développé au Québec par MEMORA solutions (L'Ancienne-Lorette), par un fondateur TDAH, avec un souci fort de la maîtrise des données : export total en un clic, authentification à deux facteurs, chiffrement, et conformité à la Loi 25 du Québec comme au RGPD.";

        $shortDescription = 'Conçu par un fondateur TDAH pour les cerveaux qui pensent vite : capture une idée en une seconde, structure-la quand tu veux, avance selon ton énergie. Palier gratuit généreux, sans carte de crédit. Fait au Québec.';

        $howToUse = "Créer un compte gratuit, par courriel ou avec un compte Google, sans carte bancaire. Arriver sur le tableau de bord d'accueil. Noter une première idée dans la « Boîte de capture », sans avoir à la classer. La retrouver et la structurer quand on veut, en tâche, note ou projet, avec le minuteur de concentration pour se lancer.";

        $coreFeatures = "Boîte de capture (noter une idée sans la classer), Minuteur de concentration, Réglages d'énergie (normal, hyperfocus, basse énergie), Carte visuelle pour penser en réseau, Tâches et priorités souples avec suivi du temps, Notes et carnets intelligents (un courriel transféré devient une note), Espaces séparés pour cloisonner ses vies, Résumé de semaine sans gamification punitive, Partage et collaboration avec reprise d'accès, API REST pour développeurs (clés API, documentation sur /docs), Serveur MCP pour brancher ses propres outils et son assistant IA préféré (Claude, ChatGPT)";

        $useCases = "Un adulte TDAH qui perd ses idées les capture immédiatement, Un étudiant cloisonne cours, travail et vie personnelle dans des espaces séparés, Un travailleur autonome suit ses projets, son temps et ses devis sans réapprendre un outil complexe, Quelqu'un qui a déjà tout essayé cherche enfin une approche pensée pour son fonctionnement";

        $pros = "Conçu pour le fonctionnement TDAH, par un fondateur TDAH, Capture ultra-rapide sans obligation de classer, Palier gratuit généreux sans carte de crédit et qui n'expire jamais, Données sous contrôle (export total, authentification à deux facteurs, chiffrement, Loi 25 et RGPD), Conçu et hébergé au Québec";

        $cons = "En bêta ouverte : jeune et en évolution, Application web sans application native iOS ou Android annoncée, Interface d'abord en français, Certaines fonctions (images dans les notes, OCR, assistant vocal) encore en pilote";

        $faq = [
            ['question' => 'Est-ce gratuit ?', 'answer' => "Oui. Le forfait gratuit ne demande aucune carte bancaire et n'expire jamais : 10 projets, 15 cahiers, 3 formations, notes et tâches illimitées. On passe au forfait payant quand on veut, sans engagement."],
            ['question' => 'Mes données sont-elles exportables ?', 'answer' => 'Oui, en un clic : une archive ZIP (projets, notes, tâches) aux formats Markdown et JSON, compatibles avec les principaux outils de notes. Synchronisation Google Drive en option.'],
            ['question' => 'Mes données sont-elles sécurisées ?', 'answer' => 'Les données sensibles sont chiffrées au repos, les communications chiffrées en transit, et la base est hébergée par une entreprise canadienne, conforme à la Loi 25 du Québec et au RGPD.'],
            ['question' => 'Faut-il un diagnostic de TDAH ?', 'answer' => "Non. Aucun diagnostic n'est demandé : l'outil est conçu pour le fonctionnement TDAH mais ouvert à toute personne."],
        ];

        $eduDetails = '50 % de rabais sur tous les forfaits payants (Essentiel, Pro, Entreprise). Admissibilité : un courriel institutionnel ou une pièce justificative. Coupon valable 12 mois, renouvelable.';

        // Décale les vedettes existantes pour placer LucidNest en première position (featured_order = 0).
        DB::table('directory_tools')->where('is_featured', true)->increment('featured_order');

        DB::table('directory_tools')->insert([
            'name' => json_encode(['fr_CA' => 'LucidNest', 'fr' => 'LucidNest'], JSON_UNESCAPED_UNICODE),
            'slug' => json_encode(['fr_CA' => 'lucidnest', 'fr' => 'lucidnest'], JSON_UNESCAPED_UNICODE),
            'aliases' => json_encode([], JSON_UNESCAPED_UNICODE),
            'description' => json_encode(['fr_CA' => $description], JSON_UNESCAPED_UNICODE),
            'short_description' => json_encode(['fr_CA' => $shortDescription], JSON_UNESCAPED_UNICODE),
            'how_to_use' => json_encode(['fr_CA' => $howToUse], JSON_UNESCAPED_UNICODE),
            'core_features' => json_encode(['fr_CA' => $coreFeatures], JSON_UNESCAPED_UNICODE),
            'use_cases' => json_encode(['fr_CA' => $useCases], JSON_UNESCAPED_UNICODE),
            'pros' => json_encode(['fr_CA' => $pros], JSON_UNESCAPED_UNICODE),
            'cons' => json_encode(['fr_CA' => $cons], JSON_UNESCAPED_UNICODE),
            'faq' => json_encode($faq, JSON_UNESCAPED_UNICODE),
            'target_audience' => json_encode(['Adultes TDAH', 'Étudiants TDAH', 'Cerveaux à pensée rapide et non linéaire'], JSON_UNESCAPED_UNICODE),
            'url' => self::URL,
            'pricing' => 'freemium',
            'status' => 'published',
            'website_type' => 'saas',
            'launch_year' => 2026,
            'lifecycle_status' => 'beta',
            'has_api_access' => 1,
            'opt_out_training' => 'no',
            'learning_curve' => 1,
            'has_education_pricing' => 1,
            'education_pricing_type' => 'discount',
            'education_pricing_details' => json_encode(['fr_CA' => $eduDetails], JSON_UNESCAPED_UNICODE),
            'education_pricing_url' => 'https://lucidnest.io/education',
            'education_discount_type' => 'student_discount',
            'education_verification_required' => 1,
            'education_official_url' => 'https://lucidnest.io/education',
            'screenshot' => 'images/directory/lucidnest-hero.jpg',
            'screenshot_locked' => 1,
            'logo' => 'images/directory/lucidnest-logo.png',
            'is_featured' => 1,
            'featured_order' => 0,
            'featured_until' => null,
            'sort_order' => 0,
            'clicks_count' => 0,
            'outbound_clicks_count' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $toolId = DB::table('directory_tools')->where('url', self::URL)->value('id');
        $categoryId = DB::table('directory_categories')
            ->where('slug', 'like', '%productivite%')
            ->value('id');

        if ($toolId && $categoryId) {
            DB::table('directory_category_tool')->insertOrIgnore([
                'directory_category_id' => $categoryId,
                'directory_tool_id' => $toolId,
            ]);
        }
    }

    public function down(): void
    {
        $tool = DB::table('directory_tools')->where('url', self::URL)->first();

        if (! $tool) {
            return;
        }

        DB::table('directory_category_tool')->where('directory_tool_id', $tool->id)->delete();
        DB::table('directory_tools')->where('id', $tool->id)->delete();
        DB::table('directory_tools')->where('is_featured', true)->where('featured_order', '>', 0)->decrement('featured_order');
    }
};
