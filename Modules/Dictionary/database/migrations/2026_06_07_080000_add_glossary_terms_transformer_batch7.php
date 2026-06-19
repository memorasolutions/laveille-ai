<?php

use Illuminate\Database\Migrations\Migration;
use Modules\Dictionary\Models\Term;

/**
 * Ajout de 3 termes « architecture Transformer » au glossaire (batch #7, catégorie 2 « Concepts fondamentaux ») :
 * Espace latent, Encodeur-décodeur, Encodage positionnel. Images via le compte Gemini de l'utilisateur.
 * Standard complet, sources vérifiées 200. Anti-doublon par slug. RÉVERSIBLE (down()).
 */
return new class extends Migration
{
    private function terms(): array
    {
        return [
            [
                'slug' => 'espace-latent',
                'name' => 'Espace latent',
                'cat' => 2, 'type' => 'concept', 'difficulty' => 'advanced', 'icon' => '🗺️',
                'definition' => "L'espace latent (latent space) est un espace vectoriel de représentation interne appris par un modèle d'apprentissage profond, où chaque point correspond à une version compressée — et non directement observable — des données d'entrée. Cet espace est généralement de dimension plus faible que les données brutes : il ne conserve que les caractéristiques essentielles qui décrivent la structure sous-jacente. Sa propriété clé est sémantique : les données qui se ressemblent y sont positionnées proches les unes des autres. Dans un auto-encodeur, un encodeur compresse l'entrée dans l'espace latent, et un décodeur la reconstruit à partir de cette représentation compacte. Les modèles génératifs l'exploitent : dans un VAE (auto-encodeur variationnel), chaque donnée correspond à une distribution de probabilité, ce qui rend l'espace continu et lisse ; dans un GAN, un vecteur de bruit latent sert de point de départ, et le faire varier transforme continûment l'image générée. L'espace latent est étroitement lié à la notion d'embedding : un embedding est une représentation vectorielle où les distances traduisent des similarités, souvent assimilée à un espace latent. Sa dimension est un choix de conception : plus elle est petite, plus la compression est forte.",
                'analogy' => "C'est comme une carte mentale des idées : au lieu des données brutes, on garde une « carte » compacte où les concepts proches sont voisins — il suffit de se déplacer un peu pour passer d'une idée à une idée semblable.",
                'example' => "Un auto-encodeur entraîné sur des visages apprend un espace latent où une direction correspond à « sourire » : en déplaçant légèrement le vecteur latent dans cette direction, le visage reconstruit se met à sourire, sans qu'on ait jamais étiqueté explicitement le sourire.",
                'did_you_know' => "Dans un espace latent bien structuré, on peut faire de l'« arithmétique » sur les concepts : des chercheurs ont montré qu'on peut additionner et soustraire des attributs (genre, lunettes, âge) en manipulant directement les vecteurs latents.",
                'one_sentence_answer' => "L'espace latent est une représentation vectorielle interne, compressée et de dimension réduite, où les données similaires sont proches les unes des autres.",
                'faq' => [
                    ['question' => "Quelle différence entre espace latent et embedding ?", 'answer' => "Les deux désignent des représentations vectorielles où la proximité traduit la similarité ; « latent » insiste sur des variables cachées d'un modèle génératif, « embedding » sur une représentation où les distances capturent les relations. En pratique, on les emploie souvent de façon interchangeable."],
                    ['question' => "Pourquoi compresser les données dans un espace latent ?", 'answer' => "Pour ne garder que les caractéristiques essentielles : cela réduit la dimension, révèle la structure sous-jacente, et permet de générer, comparer ou interpoler des données efficacement."],
                ],
                'sources' => [
                    ['label' => "DataFranca — Espace latent", 'url' => "https://datafranca.org/wiki/Espace_latent"],
                    ['label' => "IBM — Latent space", 'url' => "https://www.ibm.com/think/topics/latent-space"],
                ],
            ],
            [
                'slug' => 'encodeur-decodeur',
                'name' => 'Encodeur-décodeur',
                'cat' => 2, 'type' => 'technique', 'difficulty' => 'advanced', 'icon' => '↔️',
                'definition' => "L'architecture encodeur-décodeur (encoder-decoder) est un type de réseau de neurones composé de deux modules complémentaires. L'encodeur lit l'entrée (par exemple une phrase) et la transforme en une représentation intermédiaire compacte — un vecteur ou une séquence de vecteurs — qui en résume le sens. Le décodeur part de cette représentation pour générer la sortie, le plus souvent de façon autorégressive (token par token). Ce schéma est au cœur des modèles de séquence à séquence (seq2seq), historiquement utilisés pour la traduction automatique : l'encodeur « comprend » la phrase source, le décodeur « rédige » la phrase cible. Le Transformer original (« Attention Is All You Need », 2017) est lui-même une architecture encodeur-décodeur, où chaque module empile des couches d'attention. Selon la tâche, on n'utilise parfois qu'une moitié : les modèles de type BERT sont surtout des encodeurs (compréhension), tandis que les modèles de génération comme GPT sont surtout des décodeurs (production). Comprendre cette séparation aide à saisir comment une IA passe d'une entrée à une sortie de nature parfois différente.",
                'analogy' => "C'est comme un traducteur humain : l'encodeur est l'oreille qui écoute et comprend la phrase dans une langue (le sens), le décodeur est la bouche qui la reformule dans une autre langue.",
                'example' => "Pour traduire « Bonjour le monde » en anglais, l'encodeur compresse la phrase française en une représentation de sens, puis le décodeur génère « Hello world » mot après mot à partir de cette représentation.",
                'did_you_know' => "Beaucoup de grands modèles actuels n'utilisent qu'une moitié de l'architecture : GPT est essentiellement un décodeur, et les modèles à la BERT essentiellement un encodeur — l'architecture complète reste reine pour la traduction.",
                'one_sentence_answer' => "L'architecture encodeur-décodeur compresse l'entrée en une représentation intermédiaire (encodeur) puis génère la sortie à partir de celle-ci (décodeur).",
                'faq' => [
                    ['question' => "À quoi sert l'architecture encodeur-décodeur ?", 'answer' => "À transformer une séquence d'entrée en une séquence de sortie (seq2seq), comme en traduction automatique : l'encodeur capte le sens, le décodeur produit le résultat."],
                    ['question' => "GPT est-il un encodeur-décodeur ?", 'answer' => "GPT est essentiellement un décodeur (génération autorégressive) ; le Transformer original combinait encodeur et décodeur, mais beaucoup de modèles n'en gardent qu'une moitié selon la tâche."],
                ],
                'sources' => [
                    ['label' => "Vaswani et al. (2017) — « Attention Is All You Need »", 'url' => "https://arxiv.org/abs/1706.03762"],
                    ['label' => "Wikipédia — Auto-encodeur", 'url' => "https://fr.wikipedia.org/wiki/Auto-encodeur"],
                ],
            ],
            [
                'slug' => 'encodage-positionnel',
                'name' => 'Encodage positionnel',
                'cat' => 2, 'type' => 'technique', 'difficulty' => 'advanced', 'icon' => '📍',
                'definition' => "L'encodage positionnel (positional encoding) est le mécanisme qui ajoute à chaque token l'information de sa position dans la séquence, afin qu'un Transformer puisse tenir compte de l'ordre des mots. C'est indispensable car le mécanisme d'attention, à lui seul, est invariant par permutation : il « voit » le contenu de chaque token, mais traite tous les tokens en parallèle, sans notion d'ordre — il percevrait la phrase comme un simple « sac de mots ». Là où les réseaux récurrents (RNN, LSTM) intégraient l'ordre par leur récursivité, les Transformers, qui privilégient le calcul parallèle, doivent injecter la position par un autre canal. Concrètement, on associe à chaque position un vecteur de même dimension que l'embedding du token, puis on le combine (le plus souvent par addition) avec l'embedding de contenu : la représentation finale contient ainsi le « quoi » (le token) et le « où » (sa position). Le mécanisme a été introduit dans l'article fondateur « Attention Is All You Need » (Vaswani et al., 2017), avec des encodages sinusoïdaux ; depuis, de nombreuses variantes existent (encodages appris, relatifs, ou RoPE). Sans lui, le modèle confondrait des phrases formées des mêmes mots dans un ordre différent.",
                'analogy' => "C'est comme numéroter les wagons d'un train : les wagons (tokens) ont chacun leur contenu, mais sans numéro on ne saurait pas dans quel ordre ils sont attelés — l'encodage positionnel est ce numéro collé sur chaque wagon.",
                'example' => "« Le chat mange la souris » et « La souris mange le chat » contiennent les mêmes mots : sans encodage positionnel, le Transformer ne pourrait pas les distinguer ; avec lui, l'ordre — et donc le sens — est préservé.",
                'did_you_know' => "L'encodage positionnel sinusoïdal d'origine n'a aucun paramètre à apprendre : il se calcule par des fonctions sinus et cosinus de fréquences différentes, ce qui aide le modèle à généraliser à des longueurs de séquence jamais vues à l'entraînement.",
                'one_sentence_answer' => "L'encodage positionnel ajoute à chaque token l'information de sa place dans la séquence, pour qu'un Transformer — dont l'attention ignore l'ordre — tienne compte de la position des mots.",
                'faq' => [
                    ['question' => "Pourquoi un Transformer a-t-il besoin d'encodage positionnel ?", 'answer' => "Parce que son mécanisme d'attention traite les tokens en parallèle et est insensible à l'ordre ; sans encodage positionnel, il verrait la phrase comme un « sac de mots », sans syntaxe."],
                    ['question' => "Qu'est-ce que RoPE ?", 'answer' => "RoPE (Rotary Position Embedding) est une variante moderne d'encodage positionnel, très répandue dans les LLM récents, qui encode la position en « faisant tourner » les vecteurs plutôt qu'en les additionnant."],
                ],
                'sources' => [
                    ['label' => "Vaswani et al. (2017) — « Attention Is All You Need »", 'url' => "https://arxiv.org/abs/1706.03762"],
                    ['label' => "Dive into Deep Learning — Positional encoding", 'url' => "https://d2l.ai/chapter_attention-mechanisms-and-transformers/self-attention-and-positional-encoding.html"],
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
