<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Log;
use Modules\Dictionary\Models\Term;

/**
 * Rejoue les alias de « Prompt système » et « Superintelligence », que deux migrations
 * antérieures du même jour n'ont jamais écrits (2026-08-23).
 *
 * CE QUI S'EST PASSÉ, et pourquoi ça mérite un fichier plutôt qu'une correction discrète :
 * les deux migrations cherchaient le terme par `Term::where('slug', '...')`. Or `slug` est un
 * champ TRADUISIBLE (Spatie) : la colonne contient un JSON du type `{"fr_CA":"..."}`, et cette
 * comparaison confronte le JSON ENTIER à une chaîne simple. Elle ne correspond donc jamais.
 * Les deux migrations se sont exécutées, ont été enregistrées comme faites, et n'ont rien écrit.
 * La bonne forme, déjà utilisée par les migrations voisines, est `where('slug->fr_CA', ...)`.
 *
 * Le défaut n'a PAS été révélé par la migration elle-même - elle a rendu un succès - mais par
 * la vérification de l'EFFET sur la page publique après déploiement. C'est la seule preuve qui
 * vaille : un code de sortie ne dit pas qu'un enregistrement a changé.
 *
 * Les deux fichiers d'origine sont corrigés pour tout environnement futur ; celui-ci existe
 * parce qu'en production ils sont déjà inscrits au registre des migrations et ne rejoueront
 * jamais. Les trois sont idempotents, une double application est sans effet.
 *
 * Un terme introuvable n'est plus silencieux : il est JOURNALISÉ. La migration ne lève pas
 * d'exception pour autant - un déploiement ne doit pas échouer parce qu'une base de travail
 * n'a qu'un sous-ensemble du glossaire, qui s'écrit en production.
 *
 * RÉVERSIBLE : down() retire uniquement les alias posés ici.
 */
return new class extends Migration
{
    private const ALIAS_PAR_TERME = [
        'prompt-systeme' => [
            'instruction système',
            'consigne système',
            'message système',
            'invite système',
            'system message',
            'system instruction',
        ],
        'superintelligence' => [
            'superintelligence artificielle',
            'IA superintelligente',
            'superintelligent AI',
            'AI superintelligence',
            'künstliche Superintelligenz',
            'superinteligencia artificial',
            'superintelligenza artificiale',
            'superinteligência artificial',
            'kunstmatige superintelligentie',
        ],
    ];

    public function up(): void
    {
        foreach (self::ALIAS_PAR_TERME as $slug => $alias) {
            $term = $this->trouver($slug);

            if (! $term) {
                continue;
            }

            $term->aliases = array_values(array_unique(array_merge($term->aliases ?? [], $alias)));
            $term->save();
        }
    }

    public function down(): void
    {
        foreach (self::ALIAS_PAR_TERME as $slug => $alias) {
            $term = $this->trouver($slug);

            if (! $term) {
                continue;
            }

            $term->aliases = array_values(array_diff($term->aliases ?? [], $alias));
            $term->save();
        }
    }

    private function trouver(string $slug): ?Term
    {
        $term = Term::where('slug->fr_CA', $slug)->first()
            ?? Term::where('slug->fr', $slug)->first();

        if (! $term) {
            // Attendu sur une base de travail partielle, ANORMAL en production : dans les deux
            // cas on le dit, au lieu de laisser un « rien à faire » se confondre avec un succès.
            Log::warning("[glossaire] terme '{$slug}' introuvable : alias non appliqués.");
        }

        return $term;
    }
};
