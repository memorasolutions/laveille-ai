<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * Tâche #1910 : ajout de la fiche annuaire CanIRun.ai (alias courant : CanIRunAI, déjà couvert
 * par une actualité du site). Outil web gratuit et open source (licence MIT,
 * github.com/midudev/canirun.ai) qui détecte le matériel (GPU/RAM/CPU) directement dans le
 * navigateur et indique quels modèles IA ouverts peuvent tourner localement, sans rien envoyer
 * à un serveur. Vérifié vivant le 2026-08-30 : code HTTP 200 sur https://www.canirun.ai/
 * (redirection 307 depuis le domaine nu), contenu conforme (page réelle du produit, pas de
 * parking), et activité de développement le jour même (dernier commit GitHub à moins d'une
 * heure de la vérification, en-tête Last-Modified du jour). Idempotente : ne fait rien si une
 * fiche correspondante existe déjà (ex. environnement local sans les données de production, ou
 * migration déjà exécutée).
 */
return new class extends Migration
{
    private const URL = 'https://canirun.ai';

    public function up(): void
    {
        $exists = DB::table('directory_tools')
            ->where('url', 'like', '%canirun.ai%')
            ->exists();

        if ($exists) {
            return;
        }

        $now = now();

        $description = "## À propos de CanIRun.ai\n\n"
            ."CanIRun.ai est un outil web gratuit et libre (licence MIT) qui répond à une question précise : quels modèles d'intelligence artificielle ouverts (open weight) peuvent tourner sur votre ordinateur ? Créé par le développeur Miguel Ángel Durán (midudev), le site détecte le processeur graphique, la mémoire et le processeur directement dans le navigateur (WebGL, WebGPU, navigator.deviceMemory et un court test de calcul), sans installation ni création de compte.\n\n"
            ."Selon la documentation officielle du projet, le calcul se fait entièrement du côté client : rien n'est envoyé à un serveur. L'outil compare ensuite le matériel détecté à un catalogue de plus de 55 modèles ouverts (dont les familles Llama, Qwen, Gemma, DeepSeek, Mistral et GLM), calcule la mémoire vidéo requise selon sept niveaux de quantification (de Q2_K à F16) et attribue une note de S à F par modèle, avec une estimation de la vitesse en jetons par seconde.\n\n"
            ."Le projet est open source sur GitHub (midudev/canirun.ai, licence MIT) et développé activement, avec des mises à jour fréquentes (nouvelles cartes graphiques, nouveaux modèles, corrections). Une API JSON publique et gratuite permet aussi d'intégrer le même moteur de compatibilité dans un configurateur, un tableau de bord ou un outil interne.";

        $shortDescription = "Détecte le matériel de votre ordinateur dans le navigateur et indique quels modèles d'IA ouverts vous pouvez faire tourner localement, avec une note de compatibilité et une estimation de vitesse.";

        $howToUse = "Ouvrez canirun.ai dans un navigateur de bureau (aucun compte ni extension requis). Le site détecte automatiquement le processeur graphique, la mémoire et le processeur, puis affiche aussitôt les modèles compatibles, groupés par usage (généraliste, code, raisonnement, vision), avec une note de S à F et la quantification recommandée. Le mode « Browse all » donne accès au rapport de compatibilité complet pour tous les modèles suivis.";

        $coreFeatures = "Détection matérielle sans installation (GPU, RAM, CPU), Comparaison à plus de 55 modèles ouverts, Calcul de la mémoire vidéo requise sur 7 niveaux de quantification, Note de compatibilité de S à F par modèle, Estimation de la vitesse en jetons par seconde, Liste de tuiles (tier list) exportable en image, API JSON publique et gratuite, Commandes d'installation en un clic (Ollama, LM Studio, llama.cpp)";

        $useCases = "Choisir un modèle d'IA à faire tourner localement avant de le télécharger, Évaluer si un ordinateur ou une carte graphique convient à l'IA locale avant un achat, Comparer plusieurs modèles selon l'usage voulu (clavardage, code, raisonnement, vision), Intégrer un vérificateur de compatibilité matérielle dans un configurateur ou un outil interne via l'API";

        $pros = "Entièrement gratuit et open source (licence MIT), Aucune installation ni création de compte requise, Calcul fait localement dans le navigateur : rien n'est envoyé à un serveur, Développement actif, avec ajout régulier de nouveaux modèles et cartes graphiques, API JSON publique et gratuite";

        $cons = "Résultats fondés sur des estimations (vitesse, mémoire requise), pas sur un test de performance réel, Détection matérielle par les API du navigateur : précision variable selon le pilote graphique ou le navigateur utilisé, Projet porté par un développeur indépendant plutôt qu'une entreprise, sans support commercial formel";

        $faq = [
            ['question' => "Est-ce que CanIRun.ai est gratuit ?", 'answer' => "Oui. L'outil est entièrement gratuit, distribué sous licence libre MIT, sans compte ni abonnement."],
            ['question' => "CanIRun.ai envoie-t-il mes informations matérielles à un serveur ?", 'answer' => "Non. Selon la documentation officielle du projet, la détection du matériel et le calcul de compatibilité se font entièrement dans le navigateur ; rien n'est transmis à un serveur."],
            ['question' => "Quels modèles d'IA CanIRun.ai peut-il évaluer ?", 'answer' => "Plus de 55 modèles ouverts au moment de la rédaction de cette fiche, dont les familles Llama, Qwen, Gemma, DeepSeek, Mistral et GLM, avec de nouveaux modèles ajoutés régulièrement."],
            ['question' => "CanIRun.ai fonctionne-t-il avec les Mac Apple Silicon ?", 'answer' => "Oui. L'outil détecte les puces Apple M1 à M4 (et leurs variantes Pro, Max, Ultra) et évalue leur mémoire unifiée."],
        ];

        DB::table('directory_tools')->insert([
            'name' => json_encode(['fr_CA' => 'CanIRun.ai', 'fr' => 'CanIRun.ai'], JSON_UNESCAPED_UNICODE),
            'slug' => json_encode(['fr_CA' => 'canirunai', 'fr' => 'canirunai'], JSON_UNESCAPED_UNICODE),
            'aliases' => json_encode(['CanIRunAI', 'Can I Run AI', 'Can I Run This AI'], JSON_UNESCAPED_UNICODE),
            'description' => json_encode(['fr_CA' => $description], JSON_UNESCAPED_UNICODE),
            'short_description' => json_encode(['fr_CA' => $shortDescription], JSON_UNESCAPED_UNICODE),
            'how_to_use' => json_encode(['fr_CA' => $howToUse], JSON_UNESCAPED_UNICODE),
            'core_features' => json_encode(['fr_CA' => $coreFeatures], JSON_UNESCAPED_UNICODE),
            'use_cases' => json_encode(['fr_CA' => $useCases], JSON_UNESCAPED_UNICODE),
            'pros' => json_encode(['fr_CA' => $pros], JSON_UNESCAPED_UNICODE),
            'cons' => json_encode(['fr_CA' => $cons], JSON_UNESCAPED_UNICODE),
            'faq' => json_encode($faq, JSON_UNESCAPED_UNICODE),
            'target_audience' => json_encode(['Développeurs', "Passionnés d'IA locale", 'Acheteurs de matériel informatique'], JSON_UNESCAPED_UNICODE),
            'url' => self::URL,
            'pricing' => 'open_source',
            'status' => 'published',
            'website_type' => 'website',
            'launch_year' => 2026,
            'has_api_access' => 1,
            'opt_out_training' => 'no',
            'learning_curve' => 1,
            'is_featured' => 0,
            'sort_order' => 0,
            'clicks_count' => 0,
            'outbound_clicks_count' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $toolId = DB::table('directory_tools')->where('url', self::URL)->value('id');
        $categoryId = DB::table('directory_categories')
            ->where('slug', 'like', '%developpement%')
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
    }
};
