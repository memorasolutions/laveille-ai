<?php

use Illuminate\Database\Migrations\Migration;
use Modules\Dictionary\Models\Term;

/**
 * Ajout du terme « Latence » au glossaire (cat 2 « Concepts fondamentaux »).
 * Image via le compte Gemini de l'utilisateur. Standard complet, sources vérifiées 200.
 * Anti-doublon par slug. RÉVERSIBLE (down()).
 */
return new class extends Migration
{
    private function terms(): array
    {
        return [
            [
                'slug' => 'latence',
                'name' => 'Latence (latency)',
                'cat' => 2, 'type' => 'concept', 'difficulty' => 'intermediate', 'icon' => '⏱️',
                'definition' => "La latence (latency) est le délai entre une demande et le début de la réponse — autrement dit le temps d'attente perçu par l'utilisateur, généralement mesuré en millisecondes. En informatique, on la rencontre partout : latence réseau (temps qu'un paquet met à aller de la source à la destination, mesuré par le « ping » ou RTT), latence d'un disque, d'une mémoire ou d'un processeur. Pour l'IA générative, la latence est devenue un critère de qualité central. On distingue la latence de bout en bout (temps total entre l'envoi de la requête et la réception du dernier mot) du « temps jusqu'au premier token » (TTFT, time to first token) — l'instant où l'assistant commence à afficher sa réponse. Schématiquement, latence de bout en bout = TTFT + temps de génération du reste. C'est surtout le TTFT qui donne l'impression de réactivité dans un clavardage. La latence dépend de plusieurs facteurs : côté réseau (distance au serveur, nombre d'équipements traversés, congestion, qualité de la connexion) et côté calcul (taille du modèle, longueur du prompt et de la réponse, charge des serveurs, puissance du matériel GPU). À ne pas confondre avec le débit (throughput, nombre de tokens par seconde) : un système peut avoir un bon débit mais une latence initiale élevée, ou l'inverse. Plus la latence est faible, plus le système paraît fluide et réactif.",
                'analogy' => "C'est le temps entre le moment où vous passez commande au restaurant et celui où le serveur dépose la première assiette : peu importe la vitesse à laquelle les plats suivants arrivent ensuite (le débit), c'est ce premier délai d'attente qui détermine si le service vous semble réactif.",
                'example' => "Vous cliquez sur « Envoyer » dans un clavardage IA ; selon le modèle, le réseau et la charge des serveurs, les premiers mots apparaissent après quelques centaines de millisecondes à plusieurs secondes. Ce délai initial avant le premier mot, c'est la latence (le TTFT).",
                'did_you_know' => "Dans un clavardage IA, c'est le « temps jusqu'au premier mot » (TTFT) qui crée l'impression de réactivité, bien plus que la vitesse des mots suivants : un assistant qui commence à répondre vite « paraît » plus rapide, même s'il génère ensuite au même rythme qu'un autre.",
                'one_sentence_answer' => "La latence est le délai entre une demande et le début de la réponse ; en IA, le temps avant le premier mot (TTFT) détermine surtout l'impression de réactivité.",
                'faq' => [
                    ['question' => "Quelle différence entre latence et débit (throughput) ?", 'answer' => "La latence est le délai avant la réponse (un temps d'attente) ; le débit est la quantité traitée par seconde (par exemple les tokens par seconde). Un système peut avoir un débit élevé mais une latence initiale lente, ou l'inverse."],
                    ['question' => "Qu'est-ce qui augmente la latence d'une IA ?", 'answer' => "Côté réseau : la distance au serveur, le nombre d'équipements traversés et la congestion. Côté calcul : un modèle plus gros, un prompt ou une réponse plus longs, une forte charge serveur et un matériel moins puissant."],
                ],
                'sources' => [
                    ['label' => "Wikipédia — Latence (informatique)", 'url' => "https://fr.wikipedia.org/wiki/Latence_(informatique)"],
                    ['label' => "NVIDIA — LLM inference benchmarking metrics (TTFT, latency)", 'url' => "https://docs.nvidia.com/nim/benchmarking/llm/latest/metrics.html"],
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
