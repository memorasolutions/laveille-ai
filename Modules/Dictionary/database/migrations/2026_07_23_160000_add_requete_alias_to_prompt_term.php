<?php

use Illuminate\Database\Migrations\Migration;
use Modules\Dictionary\Models\Term;

return new class extends Migration
{
    /**
     * Le terme « Prompt » (seeder original DictionarySeeder.php, jamais retouché depuis) n'avait
     * aucun alias : « requête »/« requêtes », synonyme courant utilisé site-wide pour désigner
     * une instruction donnée à une IA, n'était donc jamais auto-lié par GlossaryLinkifier
     * (aucune couche de synonymes dans le linkifier - seulement matching exact/morphologique
     * pluriel-casse sur le `name`, jamais sur un mot différent). Signalé par l'utilisateur,
     * 2026-07-23. Ajout des DEUX formes (singulier et pluriel) explicitement, car
     * extractMorphologicalAliases() ne dérive le pluriel qu'à partir du `name` principal, jamais
     * à partir des alias déjà en base. RÉVERSIBLE : down() retire uniquement ces 2 entrées.
     */
    private const NEW_ALIASES = ['requête', 'requêtes'];

    public function up(): void
    {
        if (! class_exists(Term::class)) {
            return;
        }

        $prompt = Term::where('slug->fr_CA', 'prompt')->first();
        if (! $prompt) {
            echo "[glossaire] terme 'prompt' introuvable, migration ignorée\n";

            return;
        }

        $aliases = array_values(array_unique(array_merge($prompt->aliases ?? [], self::NEW_ALIASES)));
        $prompt->aliases = $aliases;
        $prompt->save();

        echo "[glossaire] prompt : aliases += requête, requêtes\n";
    }

    public function down(): void
    {
        if (! class_exists(Term::class)) {
            return;
        }

        $prompt = Term::where('slug->fr_CA', 'prompt')->first();
        if (! $prompt) {
            return;
        }

        $aliases = is_array($prompt->aliases) ? $prompt->aliases : [];
        $filtered = array_values(array_filter($aliases, fn ($a) => ! in_array($a, self::NEW_ALIASES, true)));
        if ($filtered !== $aliases) {
            $prompt->aliases = $filtered;
            $prompt->save();
        }
    }
};
