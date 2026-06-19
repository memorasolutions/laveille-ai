<?php

use Illuminate\Database\Migrations\Migration;
use Modules\Dictionary\Models\Term;

/**
 * Ajout de 3 termes « mécanique du RAG » au glossaire (batch P0 #2) :
 * Chunking, Recherche sémantique, Similarité cosinus (catégorie 6 « Données et traitement »).
 * Même structure/standard que batch1 (AEO, FAQPage, sources {label,url} vérifiées 200, image jpg+webp).
 * Anti-doublon par slug. RÉVERSIBLE : down() supprime par slug.
 */
return new class extends Migration
{
    private function terms(): array
    {
        return [
            [
                'slug' => 'chunking',
                'name' => 'Chunking',
                'cat' => 6, 'type' => 'technique', 'difficulty' => 'intermediate', 'icon' => '✂️',
                'definition' => "Le chunking est une technique qui consiste à découper un document en petites unités appelées « chunks » (segments), faciles à récupérer dans un pipeline RAG (génération augmentée par récupération). Chaque chunk est ensuite transformé en embedding (vecteur) et stocké dans une base vectorielle. Deux grandes stratégies existent : la taille fixe (couper tous les N tokens) et le découpage sémantique (couper quand le sujet change). Pour ne pas casser une idée à la frontière de deux segments, on ajoute souvent un chevauchement (overlap) de 10 à 20 %, soit environ 50 à 100 tokens. Les tailles courantes vont de 256 à 1024 tokens, 512 étant une valeur fréquente. Un chunk trop long mélange plusieurs idées dans un seul vecteur et dégrade la précision de la recherche ; trop court, il perd le contexte. Le chunking est donc une étape clé qui conditionne directement la qualité des réponses d'un système RAG.",
                'analogy' => "C'est comme découper un long livre en fiches de lecture thématiques : au lieu de relire tout l'ouvrage, on retrouve d'un coup la fiche qui contient exactement le passage cherché.",
                'example' => "Un guide technique de 20 pages est découpé en chunks de 512 tokens avec 10 % de chevauchement, ce qui donne une quarantaine de segments vectorisés. À une question de l'utilisateur, le système ne récupère que les 5 chunks les plus proches du sens de la question et les transmet au modèle.",
                'did_you_know' => "Il n'existe pas de taille de chunk universelle : selon des bancs d'essai récents, un simple découpage à taille fixe d'environ 512 tokens rivalise souvent avec des méthodes sémantiques bien plus complexes.",
                'one_sentence_answer' => "Le chunking est le découpage d'un document en petits segments récupérables, étape clé qui conditionne la qualité d'un système RAG.",
                'faq' => [
                    ['question' => "Pourquoi ajouter un chevauchement (overlap) entre les chunks ?", 'answer' => "Pour éviter de couper une phrase ou une idée en plein milieu : répéter 10 à 20 % du segment précédent préserve le contexte aux frontières et améliore la récupération."],
                    ['question' => "Quelle taille de chunk choisir ?", 'answer' => "Souvent 256 à 1024 tokens (512 est courant) : trop long, le vecteur mélange plusieurs idées ; trop court, il perd le contexte. Le bon réglage dépend du type de document."],
                ],
                'sources' => [
                    ['label' => "Pinecone — Chunking strategies for RAG", 'url' => "https://www.pinecone.io/learn/chunking-strategies/"],
                    ['label' => "IBM — Retrieval-augmented generation (RAG)", 'url' => "https://www.ibm.com/think/topics/retrieval-augmented-generation"],
                ],
            ],
            [
                'slug' => 'recherche-semantique',
                'name' => 'Recherche sémantique',
                'cat' => 6, 'type' => 'technique', 'difficulty' => 'intermediate', 'icon' => '🔎',
                'definition' => "La recherche sémantique cherche par le sens plutôt que par la correspondance exacte de mots-clés. Elle transforme la requête et les documents en embeddings (vecteurs numériques) et identifie les documents dont le vecteur est le plus proche de celui de la requête (recherche des plus proches voisins dans l'espace vectoriel). Cette approche comprend les synonymes et l'intention : une requête « voiture » peut ainsi retrouver des textes parlant d'« automobile » ou de « véhicule », même si le mot exact n'y figure pas. Elle est au cœur du RAG et des moteurs de recherche modernes, où elle remplace ou complète la recherche lexicale classique. Sa qualité dépend du modèle d'embedding utilisé et de la façon dont les documents ont été découpés (chunking) et indexés.",
                'analogy' => "C'est comme un libraire d'expérience : tu lui décris vaguement « le roman avec un vieux pêcheur et un gros poisson » et il te tend « Le vieil homme et la mer », sans que tu aies eu besoin du titre exact.",
                'example' => "Un utilisateur tape « comment réduire ma facture d'électricité ». La recherche sémantique remonte un article intitulé « 10 astuces pour diminuer sa consommation énergétique », alors qu'aucun des mots de la requête n'y apparaît littéralement.",
                'did_you_know' => "Contrairement à la recherche par mots-clés, la recherche sémantique peut retrouver un document pertinent même s'il ne contient aucun mot exact de la requête, en se basant uniquement sur la proximité de sens.",
                'one_sentence_answer' => "La recherche sémantique trouve l'information par le sens, en comparant des vecteurs (embeddings), plutôt que par la correspondance exacte de mots-clés.",
                'faq' => [
                    ['question' => "Quelle différence avec la recherche par mots-clés ?", 'answer' => "La recherche par mots-clés exige une correspondance littérale des termes ; la recherche sémantique compare le SENS via des embeddings et retrouve donc synonymes et reformulations."],
                    ['question' => "Quel est le lien avec le RAG ?", 'answer' => "Le RAG s'appuie sur la recherche sémantique pour retrouver, dans une base vectorielle, les passages les plus pertinents à fournir au modèle de langage."],
                ],
                'sources' => [
                    ['label' => "Wikipédia — Recherche sémantique", 'url' => "https://fr.wikipedia.org/wiki/Recherche_s%C3%A9mantique"],
                    ['label' => "Elastic — What is semantic search?", 'url' => "https://www.elastic.co/what-is/semantic-search"],
                ],
            ],
            [
                'slug' => 'similarite-cosinus',
                'name' => 'Similarité cosinus',
                'cat' => 6, 'type' => 'technique', 'difficulty' => 'advanced', 'icon' => '📐',
                'definition' => "La similarité cosinus mesure à quel point deux vecteurs pointent dans la même direction, en calculant le cosinus de l'angle qui les sépare : cos(θ) = A·B / (||A||·||B||). Le résultat va de −1 (directions opposées) à 1 (même direction), 0 signifiant que les vecteurs sont orthogonaux, donc sans rapport. Pour des embeddings de texte, les valeurs se situent souvent entre 0 et 1. Sa particularité est d'être insensible à la magnitude : seule l'orientation des vecteurs compte, pas leur longueur. C'est pourquoi elle est la mesure de référence pour comparer des embeddings et classer les résultats d'une recherche sémantique ou d'un système RAG : plus le score est proche de 1, plus deux textes sont jugés sémantiquement proches.",
                'analogy' => "C'est comme comparer la direction de deux flèches plutôt que leur longueur : deux flèches qui pointent au même endroit sont « similaires », qu'elles soient courtes ou longues.",
                'example' => "Deux phrases au sens très proche donnent des embeddings presque alignés, avec une similarité cosinus de l'ordre de 0,92 ; deux phrases sans rapport donnent un score proche de 0.",
                'did_you_know' => "La similarité cosinus ignore complètement la « taille » des vecteurs : deux documents, l'un court et l'autre long, peuvent obtenir un score parfait de 1 s'ils traitent exactement du même sujet.",
                'one_sentence_answer' => "La similarité cosinus mesure la proximité de sens entre deux vecteurs par l'angle qui les sépare, sur une échelle où 1 signifie « même direction ».",
                'faq' => [
                    ['question' => "Pourquoi utiliser le cosinus plutôt que la distance ?", 'answer' => "Parce qu'il compare l'orientation des vecteurs sans tenir compte de leur longueur : deux textes de tailles très différentes mais de même sujet obtiennent un score élevé."],
                    ['question' => "Que signifie un score de 0 ?", 'answer' => "Un score de 0 signifie que les deux vecteurs sont orthogonaux, c'est-à-dire sans rapport sémantique ; proche de 1, ils sont très similaires."],
                ],
                'sources' => [
                    ['label' => "Wikipédia — Similarité cosinus", 'url' => "https://fr.wikipedia.org/wiki/Similarit%C3%A9_cosinus"],
                    ['label' => "IBM — Vector search", 'url' => "https://www.ibm.com/think/topics/vector-search"],
                ],
            ],
        ];
    }

    public function up(): void
    {
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
