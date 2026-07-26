<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Modules\Dictionary\Models\Term;

/**
 * Fix bug systémique (2026-07-26, découvert via signalement user : "licence MIT" non soulignée dans
 * l'article OpenClaw malgré le terme existant au glossaire).
 *
 * 25 termes ajoutés entre le 2026-07-21 et le 2026-07-25 ont `match_strategy = 'exact'`, une valeur
 * INVALIDE non reconnue par GlossaryLinkifier::matchInText() (seules 'loose', 'case_sensitive',
 * 'partial_case_sensitive', 'exact_phrase', 'never_auto' sont gérées - voir le switch ligne ~688).
 * Effet réel : le code tombe dans la branche par défaut (pas de flag 'i' insensible à la casse),
 * donc 'exact' se comporte comme 'case_sensitive' - correspondance stricte à la casse EXACTE du nom
 * du terme.
 *
 * Pour les 20 termes de licences (slugs licence-*, gnu-*, cc0-*, the-unlicense, the-postgresql-license,
 * sil-open-font-license-1-1, creative-commons-licences-de-contenu), le nom commence par le mot
 * français commun "Licence" - or en prose naturelle, "licence" s'écrit presque toujours en minuscule
 * en milieu de phrase (ex. "sous licence MIT", jamais "sous Licence MIT"). Résultat : ces 20 termes
 * ne se sont JAMAIS auto-liés correctement depuis leur création, malgré des vérifications visuelles
 * qui n'avaient pas testé ce cas précis en contexte de phrase réelle. Fix : 'loose' (insensible à la
 * casse) - sûr ici car "Licence X" est une phrase multi-mots spécifique, aucune collision avec
 * STOP_LIST_FR (mots français courants ambigus).
 *
 * Pour les 5 autres termes (openclaw, sudo, mitre-attck, tcc, laravel-herd), leurs noms sont des noms
 * propres/acronymes déjà écrits dans leur casse canonique en prose (aucun symptôme observé) - fix
 * 'case_sensitive' = simple normalisation vers une valeur documentée, comportement strictement
 * identique à 'exact' (no-op fonctionnel, juste hygiène de données).
 *
 * Réversible : down() restaure 'exact' pour les 25 ids (valeur d'origine, même si invalide).
 */
return new class extends Migration
{
    private const LOOSE_SLUGS = [
        'licence-mit', 'licence-apache-2-0', 'licence-bsd-2-clause', 'licence-bsd-3-clause',
        'licence-isc', 'licence-zlib', 'licence-boost-software-1-0', 'the-unlicense',
        'cc0-1-0-universal', 'creative-commons-licences-de-contenu', 'the-postgresql-license',
        'sil-open-font-license-1-1', 'gnu-gpl-v2', 'gnu-gpl-v3', 'gnu-agpl-v3', 'gnu-lgpl',
        'licence-mpl-2-0', 'licence-epl-2-0', 'licence-cddl-1-0-1-1', 'licence-artistic-2-0',
    ];

    private const CASE_SENSITIVE_SLUGS = [
        'openclaw', 'sudo', 'mitre-attck', 'tcc', 'laravel-herd',
    ];

    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }
        if (! class_exists(Term::class)) {
            return;
        }

        $looseCount = Term::whereIn('slug', self::LOOSE_SLUGS)
            ->where('match_strategy', 'exact')
            ->update(['match_strategy' => 'loose']);

        $caseSensitiveCount = Term::whereIn('slug', self::CASE_SENSITIVE_SLUGS)
            ->where('match_strategy', 'exact')
            ->update(['match_strategy' => 'case_sensitive']);

        echo "[glossaire] match_strategy corrigé : {$looseCount} vers 'loose' (licences), {$caseSensitiveCount} vers 'case_sensitive' (noms propres/acronymes)\n";
    }

    public function down(): void
    {
        if (! class_exists(Term::class)) {
            return;
        }

        Term::whereIn('slug', array_merge(self::LOOSE_SLUGS, self::CASE_SENSITIVE_SLUGS))
            ->update(['match_strategy' => 'exact']);
    }
};
