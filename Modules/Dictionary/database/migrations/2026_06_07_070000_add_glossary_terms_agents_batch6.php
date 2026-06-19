<?php

use Illuminate\Database\Migrations\Migration;
use Modules\Dictionary\Models\Term;

/**
 * Ajout de 3 termes « agents & sûreté 2026 » au glossaire (batch #6, catégorie 1 « IA ») :
 * Garde-fous (guardrails), A2A (Agent-to-Agent), Effondrement de modèle (model collapse).
 * Images via le compte Gemini de l'utilisateur (Playwright). Standard complet, sources vérifiées 200.
 * Anti-doublon par slug. RÉVERSIBLE (down()).
 */
return new class extends Migration
{
    private function terms(): array
    {
        return [
            [
                'slug' => 'garde-fous',
                'name' => 'Garde-fous (guardrails)',
                'cat' => 1, 'type' => 'technique', 'difficulty' => 'intermediate', 'icon' => '🛡️',
                'definition' => "Les garde-fous (guardrails) sont des mécanismes de sécurité — techniques et de politique — qui encadrent les entrées et les sorties d'un modèle d'IA générative pour empêcher des comportements dangereux, non conformes ou hors-périmètre. Ils forment une couche défensive distincte du modèle lui-même : avant qu'une requête n'atteigne le modèle (garde-fous d'entrée) et avant qu'une réponse ne parvienne à l'utilisateur ou à un autre système (garde-fous de sortie). À l'entrée, ils détectent et bloquent les injections de requête (prompt injection) et tentatives de jailbreak, masquent les données personnelles ou sensibles (PII), filtrent le contenu toxique ou illégal et valident le format. À la sortie, ils bloquent le contenu interdit, empêchent les fuites d'informations confidentielles, vérifient la conformité aux politiques (ne pas donner d'avis médical hors cadre, etc.) et imposent un format strict (JSON valide). Ils s'appuient sur des règles déterministes, des classifieurs ou d'autres modèles filtrants, et peuvent régénérer une réponse (re-prompting) si elle viole une règle. Les garde-fous sont essentiels pour déployer un assistant IA de façon sûre et conforme (AI Act, lois locales).",
                'analogy' => "C'est comme les garde-fous d'un pont et un agent de sécurité à l'entrée : ils ne conduisent pas la voiture (le modèle), mais ils empêchent d'aller dans le décor et filtrent ce qui entre et ce qui sort.",
                'example' => "Un robot conversationnel d'entreprise : à l'entrée, un garde-fou masque le numéro de carte bancaire qu'un client a collé ; à la sortie, un autre bloque une réponse qui révélerait les données d'un autre client et force un format de ticket valide.",
                'did_you_know' => "Les garde-fous sont une couche SÉPARÉE du modèle : on peut renforcer la sécurité d'un assistant sans réentraîner le LLM, simplement en ajustant les filtres d'entrée et de sortie.",
                'one_sentence_answer' => "Les garde-fous sont des mécanismes de contrôle appliqués aux entrées et sorties d'un LLM pour bloquer le contenu interdit, sensible ou dangereux et assurer la conformité.",
                'faq' => [
                    ['question' => "Quelle différence entre garde-fous d'entrée et de sortie ?", 'answer' => "Les garde-fous d'entrée filtrent ce qui est envoyé au modèle (injections, PII, toxicité) ; ceux de sortie filtrent ce que le modèle renvoie (contenu interdit, fuites, format) avant affichage."],
                    ['question' => "Les garde-fous remplacent-ils l'alignement du modèle ?", 'answer' => "Non : ils s'ajoutent par-dessus. L'alignement rend le modèle plus sûr par nature ; les garde-fous sont une couche de contrôle externe et déterministe, en complément."],
                ],
                'sources' => [
                    ['label' => "IBM — AI guardrails", 'url' => "https://www.ibm.com/think/topics/ai-guardrails"],
                    ['label' => "Microsoft Learn — Azure AI Content Safety", 'url' => "https://learn.microsoft.com/en-us/azure/ai-services/content-safety/overview"],
                ],
            ],
            [
                'slug' => 'a2a',
                'name' => 'A2A (Agent-to-Agent)',
                'cat' => 1, 'type' => 'technique', 'difficulty' => 'advanced', 'icon' => '🤝',
                'definition' => "A2A (Agent2Agent, ou « agent à agent ») est un protocole ouvert de communication entre agents d'IA, introduit par Google lors de Google Cloud Next en avril 2025, puis confié à un projet open source de la Linux Foundation. Son but : résoudre la fragmentation des écosystèmes d'agents en offrant un « langage commun » qui permet à des agents issus de fournisseurs et de frameworks différents de se découvrir, de déléguer des tâches et de partager des résultats — sans exposer leur implémentation interne (ils restent des « boîtes noires »). Techniquement, A2A est un protocole de messagerie normalisé fondé sur JSON-RPC 2.0 sur HTTP(S), avec échanges synchrones, streaming (SSE) et notifications asynchrones, et l'échange de données riches (texte, fichiers, JSON). Il faut le distinguer du MCP (Model Context Protocol), complémentaire : MCP relie un agent à des OUTILS et des DONNÉES, tandis qu'A2A relie un agent à un AUTRE AGENT. Ensemble, ils forment la base de l'interopérabilité multi-agents de 2025-2026 : un agent orchestrateur peut déléguer un sous-problème à un agent spécialisé tournant sur une autre stack ou un autre cloud.",
                'analogy' => "Si MCP est la prise qui branche un agent à ses outils, A2A est le téléphone commun qui permet à deux agents — même de marques différentes — de se parler et de se répartir le travail.",
                'example' => "Un agent « RH » reçoit une candidature et, via A2A, délègue l'analyse du CV à un agent spécialisé d'un autre éditeur, puis récupère le résultat — chacun ignorant le fonctionnement interne de l'autre.",
                'did_you_know' => "A2A et MCP ne sont pas concurrents mais complémentaires : MCP connecte un agent à ses outils et données, A2A connecte des agents entre eux. Beaucoup d'architectures 2026 utilisent les deux.",
                'one_sentence_answer' => "A2A est un protocole ouvert (Google, 2025) qui permet à des agents IA de fournisseurs différents de communiquer, déléguer des tâches et collaborer entre eux.",
                'faq' => [
                    ['question' => "Quelle différence entre A2A et MCP ?", 'answer' => "MCP (Model Context Protocol) relie un agent à des outils et des données ; A2A relie un agent à un autre agent. Ils sont complémentaires."],
                    ['question' => "Qui a créé A2A ?", 'answer' => "Google l'a présenté en avril 2025 à Google Cloud Next ; le protocole est désormais open source, hébergé sous l'égide de la Linux Foundation."],
                ],
                'sources' => [
                    ['label' => "A2A Project (GitHub)", 'url' => "https://github.com/a2aproject/A2A"],
                    ['label' => "IBM — Agent2Agent (A2A) protocol", 'url' => "https://www.ibm.com/think/topics/agent2agent-protocol"],
                ],
            ],
            [
                'slug' => 'effondrement-de-modele',
                'name' => 'Effondrement de modèle (model collapse)',
                'cat' => 1, 'type' => 'concept', 'difficulty' => 'advanced', 'icon' => '🌀',
                'definition' => "L'effondrement de modèle (model collapse) désigne la dégradation progressive des performances d'un modèle d'IA générative lorsqu'il est entraîné de façon répétée sur des données synthétiques — générées par d'autres modèles ou par lui-même — plutôt que sur des données humaines originales. Le mécanisme est une boucle de rétroaction : un modèle génère du contenu, ce contenu est réinjecté dans les jeux d'entraînement des modèles suivants, et ainsi de suite. À chaque génération, les caractéristiques rares ou minoritaires des données d'origine sont sous-représentées puis perdues : la distribution apprise se « contracte », la diversité chute, le modèle se recale sur les cas les plus fréquents et les erreurs se propagent. Une étude marquante d'Ilia Shumailov et al., publiée dans Nature en 2024, a démontré — par la théorie et l'expérience — que l'entraînement de générations successives sur des données de plus en plus synthétiques mène à l'effondrement : en quelques itérations, le contenu peut dégénérer en absurdités. C'est un risque réel à l'heure où le web se remplit de contenu généré par IA, susceptible de polluer les futurs jeux d'entraînement.",
                'analogy' => "C'est comme photocopier la photocopie d'une photocopie : à chaque copie, les détails fins disparaissent et l'image devient de plus en plus floue et déformée, jusqu'à devenir illisible.",
                'example' => "Si on entraîne un modèle d'images uniquement sur des images générées par IA, génération après génération, les visages perdent en variété et en réalisme jusqu'à se ressembler tous — la richesse des données humaines d'origine s'efface.",
                'did_you_know' => "L'étude de référence (Shumailov et al., Nature 2024) montre qu'en quelques générations seulement d'entraînement sur des données synthétiques, un modèle peut perdre les événements rares puis basculer dans l'incohérence — ce qui donne une valeur croissante aux vraies données humaines.",
                'one_sentence_answer' => "L'effondrement de modèle est la dégradation d'un modèle d'IA entraîné en boucle sur des données générées par IA, qui perd diversité et qualité jusqu'à produire des absurdités.",
                'faq' => [
                    ['question' => "Pourquoi le contenu généré par IA est-il un risque pour l'entraînement ?", 'answer' => "Parce qu'en réinjectant des données synthétiques génération après génération, le modèle perd les cas rares et la diversité des données humaines originales, ce qui dégrade ses sorties (effondrement)."],
                    ['question' => "Comment éviter l'effondrement de modèle ?", 'answer' => "En préservant une part suffisante de données humaines originales et de qualité dans l'entraînement, et en traçant/filtrant les contenus synthétiques."],
                ],
                'sources' => [
                    ['label' => "Nature (2024) — Shumailov et al., « AI models collapse when trained on recursively generated data »", 'url' => "https://www.nature.com/articles/s41586-024-07566-y"],
                    ['label' => "IBM — Model collapse", 'url' => "https://www.ibm.com/think/topics/model-collapse"],
                ],
            ],
        ];
    }

    public function up(): void
    {
        if (\Illuminate\Support\Facades\DB::getDriverName() !== 'mysql') {
            return;
        }
        if (! class_exists(Term::class)) {
            echo "[glossaire] modèle Term absent — ignoré\n";
            return;
        }

        // Cette migration insère des données avec des FK vers dictionary_categories
        // qui n'existent que sur MySQL (seedées en prod). SQLite en tests = skip.
        if (\Illuminate\Support\Facades\DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        foreach ($this->terms() as $t) {
            if (Term::where('slug->fr_CA', $t['slug'])->exists()) {
                echo "[glossaire] slug déjà présent, skip : {$t['slug']}\n";
                continue;
            }
            $term = new Term();
            foreach (['name', 'slug', 'definition', 'analogy', 'example', 'did_you_know', 'one_sentence_answer'] as $tf) {
                $term->setTranslations($tf, ['fr_CA' => $t[$tf], 'fr' => $t[$tf]]);
            }
            $term->faq = $t['faq'];
            $term->sources = $t['sources'];
            $term->difficulty = $t['difficulty'];
            $term->icon = $t['icon'];
            $term->type = $t['type'];
            $term->dictionary_category_id = $t['cat'];
            $term->hero_image = 'images/glossaire/'.$t['slug'].'.webp';
            $term->is_published = true;
            $term->match_strategy = 'loose';
            $term->save();
            echo "[glossaire] inséré : {$t['slug']} (id={$term->id})\n";
        }
    }

    public function down(): void
    {
        if (! class_exists(Term::class)) {
            return;
        }
        foreach ($this->terms() as $t) {
            Term::where('slug->fr_CA', $t['slug'])->delete();
        }
    }
};
