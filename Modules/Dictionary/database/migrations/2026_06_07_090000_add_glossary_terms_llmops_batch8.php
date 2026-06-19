<?php

use Illuminate\Database\Migrations\Migration;
use Modules\Dictionary\Models\Term;

/**
 * Ajout de 3 termes « fiabilité LLM/RAG » au glossaire (batch #8) :
 * Reranking (cat 6 « Données »), Grounding (cat 1 « IA »), Sortie structurée (cat 1 « IA »).
 * Images via le compte Gemini de l'utilisateur. Standard complet, sources vérifiées 200.
 * Anti-doublon par slug. RÉVERSIBLE (down()).
 */
return new class extends Migration
{
    private function terms(): array
    {
        return [
            [
                'slug' => 'reranking',
                'name' => 'Reranking (reclassement)',
                'cat' => 6, 'type' => 'technique', 'difficulty' => 'intermediate', 'icon' => '🔃',
                'definition' => "Le reranking (reclassement) est une deuxième étape d'un pipeline RAG, qui réordonne par pertinence les documents déjà récupérés avant de les transmettre au modèle de langage. Le principe : une première recherche large et rapide (par similarité d'embeddings ou BM25) renvoie un grand ensemble de candidats — par exemple les 50 passages les plus proches. Mais cette recherche initiale, rapide, ramène souvent des passages thématiquement proches qui ne répondent pas précisément à la question. Le reranker corrige ce défaut : un modèle plus précis, typiquement un cross-encoder, relit chaque paire « requête + passage », attribue un nouveau score de pertinence et réordonne les candidats, pour ne garder que les meilleurs (top 3 à 10). Ces passages, mieux alignés sur la question, sont ensuite injectés dans le prompt du LLM. L'ajout d'un reranker augmente nettement la précision de la récupération et réduit les hallucinations, puisque le modèle s'appuie sur un sous-ensemble plus pertinent. C'est une brique souvent négligée mais à fort impact : on parle d'une architecture « en deux étages » (recherche large → reranking → génération).",
                'analogy' => "C'est comme un jury en deux tours : un premier filtre rapide retient 50 candidats, puis un examinateur expert relit chacun en détail face à la question précise et ne garde que les 3 meilleurs pour la finale.",
                'example' => "À la question « Quel est le délai de rétractation pour un achat en ligne au Québec ? », la recherche vectorielle ramène 50 passages sur le commerce électronique ; le reranker relit chacun et fait remonter en tête les 3 qui mentionnent précisément le délai, qui seront seuls fournis au modèle.",
                'did_you_know' => "Le cross-encoder d'un reranker est plus précis mais plus lent que la recherche vectorielle : c'est pourquoi on ne l'applique qu'à une cinquantaine de candidats déjà filtrés, et non à toute la base — un bon compromis vitesse/précision.",
                'one_sentence_answer' => "Le reranking est la deuxième étape d'un RAG qui réordonne les documents récupérés avec un modèle plus précis (cross-encoder), pour ne garder que les plus pertinents avant la génération.",
                'faq' => [
                    ['question' => "Pourquoi ajouter un reranker à un RAG ?", 'answer' => "Parce que la recherche vectorielle seule ramène souvent des passages proches du sujet mais imprécis ; le reranker les réordonne finement par pertinence, ce qui augmente la précision et réduit les hallucinations."],
                    ['question' => "Qu'est-ce qu'un cross-encoder ?", 'answer' => "Un modèle qui évalue ensemble la requête ET un passage pour leur attribuer un score de pertinence très précis ; plus lent que la recherche par embeddings, on l'utilise sur un petit nombre de candidats."],
                ],
                'sources' => [
                    ['label' => "Pinecone — Rerankers and two-stage retrieval", 'url' => "https://www.pinecone.io/learn/series/rag/rerankers/"],
                    ['label' => "Jina AI — Reranker pour la pertinence et le RAG", 'url' => "https://jina.ai/news/maximizing-search-relevancy-and-rag-accuracy-with-jina-reranker/"],
                ],
            ],
            [
                'slug' => 'grounding',
                'name' => 'Grounding (ancrage)',
                'cat' => 1, 'type' => 'technique', 'difficulty' => 'intermediate', 'icon' => '⚓',
                'definition' => "Le grounding (ancrage) consiste à ancrer les réponses d'un modèle de langage dans des sources externes vérifiables — documents, bases de connaissances, API ou recherche web — plutôt que de les laisser reposer uniquement sur ses paramètres internes (ce qu'il a « mémorisé » à l'entraînement). L'objectif : qu'une réponse générée soit compatible avec une source fiable et identifiable. Le cas d'usage le plus courant est le RAG : le système récupère d'abord des passages pertinents, les injecte dans le contexte du modèle, puis génère la réponse à partir de ces éléments — souvent avec citation des sources, ce qui rend la réponse vérifiable. Le grounding est essentiel pour réduire les hallucinations (réponses plausibles mais non fondées) et pour les cas où l'information doit être fraîche, spécialisée ou traçable. C'est une tendance forte de 2025-2026 : OpenAI, Microsoft (Grounding with Bing) et Google (Vertex AI) proposent tous des mécanismes d'ancrage. Attention toutefois : le grounding réduit le risque d'erreur mais ne l'élimine pas ; un contrôle de qualité reste nécessaire.",
                'analogy' => "C'est comme exiger d'un élève qu'il cite ses sources plutôt que de répondre « de mémoire » : il a moins de chances d'inventer, et on peut vérifier d'où vient l'information.",
                'example' => "Un assistant juridique « ancré » ne répond pas de tête : il récupère d'abord les articles de loi pertinents, génère la réponse à partir de ces textes et affiche les références — ce qui permet de vérifier et réduit fortement le risque d'invention.",
                'did_you_know' => "Le grounding ne supprime pas totalement les hallucinations : un modèle peut encore mal interpréter une source ou extrapoler. C'est pourquoi l'attribution des sources (citations) reste indispensable pour la vérifiabilité.",
                'one_sentence_answer' => "Le grounding est l'ancrage des réponses d'un LLM dans des sources externes vérifiables (souvent via RAG), avec attribution, afin de réduire les hallucinations et d'augmenter la fiabilité.",
                'faq' => [
                    ['question' => "Quel lien entre grounding et RAG ?", 'answer' => "Le RAG est la principale technique de grounding : il récupère des sources fiables et les fournit au modèle pour qu'il ancre sa réponse dessus, au lieu de répondre uniquement de mémoire."],
                    ['question' => "Le grounding élimine-t-il les hallucinations ?", 'answer' => "Non : il les réduit fortement mais ne les supprime pas. Le modèle peut encore mal interpréter une source ; l'attribution des sources et un contrôle qualité restent nécessaires."],
                ],
                'sources' => [
                    ['label' => "Google Cloud — Grounding overview (Vertex AI)", 'url' => "https://cloud.google.com/vertex-ai/generative-ai/docs/grounding/overview"],
                    ['label' => "IBM — Hallucinations de l'IA", 'url' => "https://www.ibm.com/think/topics/ai-hallucinations"],
                ],
            ],
            [
                'slug' => 'sortie-structuree',
                'name' => 'Sortie structurée',
                'cat' => 1, 'type' => 'technique', 'difficulty' => 'intermediate', 'icon' => '🗂️',
                'definition' => "La sortie structurée (structured output, ou « JSON mode ») consiste à contraindre un modèle de langage à produire sa réponse dans un format de données prédéfini — le plus souvent du JSON — respectant un schéma explicite, afin qu'elle soit directement exploitable par du code ou un agent logiciel, sans avoir à « parser » du texte libre. On distingue deux niveaux. Le « JSON mode » simple force le modèle à renvoyer du JSON syntaxiquement valide, mais sans garantie forte de conformité à un schéma (des champs peuvent manquer, des types être incorrects). Les « structured outputs » basés sur schéma vont plus loin : on fournit un JSON Schema (ou une signature d'outil), et le modèle utilise un décodage contraint (constrained decoding) qui empêche la génération de tokens qui violeraient le schéma — type incompatible, clé inconnue ou champ obligatoire manquant deviennent impossibles. Les principaux fournisseurs (OpenAI, Anthropic, Google, Cohere) exposent ces modes via des paramètres dédiés (json_schema, strict=true). Côté application, des bibliothèques comme Pydantic (Python) ou Zod (TypeScript) valident la réponse et lèvent une erreur en cas d'écart. La sortie structurée est indispensable pour fiabiliser l'intégration d'un LLM dans une application ou un agent.",
                'analogy' => "C'est comme remplir un formulaire à cases plutôt que d'écrire une lettre libre : au lieu d'un texte qu'il faut interpréter, on obtient des champs nets et prévisibles que le système peut lire directement.",
                'example' => "Pour extraire les informations d'une facture, on demande au modèle une sortie structurée selon un schéma {fournisseur, date, montant, devise} : on récupère un JSON propre directement utilisable, au lieu d'un paragraphe qu'il faudrait analyser à la main.",
                'did_you_know' => "Avec le « décodage contraint », le modèle ne peut littéralement pas produire un JSON invalide ou hors-schéma : à chaque token, seuls ceux qui respectent le schéma sont autorisés — la conformité est garantie par construction, pas vérifiée après coup.",
                'one_sentence_answer' => "La sortie structurée force un LLM à répondre selon un format et un schéma stricts (souvent JSON), pour une réponse directement exploitable par du code ou un agent.",
                'faq' => [
                    ['question' => "Différence entre JSON mode et structured outputs ?", 'answer' => "Le JSON mode garantit un JSON syntaxiquement valide, mais pas forcément conforme à un schéma ; les structured outputs basés sur schéma garantissent en plus que la structure et les types respectent le schéma fourni."],
                    ['question' => "Pourquoi utiliser une sortie structurée ?", 'answer' => "Pour intégrer fiablement un LLM dans une application ou un agent : une réponse au format strict est directement lisible par le code, sans parsing fragile de texte libre."],
                ],
                'sources' => [
                    ['label' => "OpenAI — Structured outputs", 'url' => "https://platform.openai.com/docs/guides/structured-outputs"],
                    ['label' => "JSON Schema (spécification)", 'url' => "https://json-schema.org/"],
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
