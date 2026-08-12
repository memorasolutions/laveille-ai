<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Ajoute les 3 verbes de recherche a la liste du constructeur de prompts.
 *
 * POURQUOI UNE MIGRATION ET PAS SEULEMENT LE SEEDER : la liste des verbes vit a DEUX
 * endroits, et c'est la base qui gagne. Settings::get('tools.prompt_builder.verbs', $defaultVerbs)
 * retourne la ligne `settings` des qu'elle existe, et ne consulte JAMAIS le tableau de repli
 * ecrit dans la vue Blade. Or le pipeline de deploiement execute `php artisan migrate` mais
 * PAS `php artisan db:seed` : sans cette migration, la production garderait les 14 anciens
 * verbes et les 3 nouveaux resteraient invisibles en ligne malgre un code correct.
 */
return new class extends Migration
{
    private const KEY = 'tools.prompt_builder.verbs';

    private const VALUES_TO_ADD = [
        'Recherche',
        'Recherche sur Internet, en priorisant les sites officiels et pertinents',
        'Recherche en profondeur, Internet inclus',
    ];

    public function up(): void
    {
        // N'ajoute que ce qui manque : rejouer la migration ne cree jamais de doublon.
        $this->updateVerbs(function (array $verbs): array {
            $aAjouter = [];

            foreach (self::VALUES_TO_ADD as $valeur) {
                if (! in_array($valeur, $verbs, true)) {
                    $aAjouter[] = $valeur;
                }
            }

            return array_merge($verbs, $aAjouter);
        });
    }

    public function down(): void
    {
        // Retire uniquement ces 3 valeurs, preserve tout le reste et son ordre.
        $this->updateVerbs(function (array $verbs): array {
            $restants = array_filter($verbs, static function ($verbe): bool {
                return ! in_array($verbe, self::VALUES_TO_ADD, true);
            });

            // Reindexation obligatoire : sans elle, json_encode produirait un objet, pas un tableau.
            return array_values($restants);
        });
    }

    /**
     * Logique commune : lire, decoder, transformer, reecrire.
     *
     * Sort silencieusement si la ligne est absente ou si la valeur stockee n'est pas un
     * tableau JSON valide. Une exception ici ferait echouer `migrate` et bloquerait le
     * deploiement complet pour un reglage d'affichage : le repli sur du silence est voulu.
     */
    private function updateVerbs(callable $transformation): void
    {
        if (! Schema::hasTable('settings')) {
            return;
        }

        $ligne = DB::table('settings')->where('key', self::KEY)->first();

        if ($ligne === null) {
            return;
        }

        $decode = json_decode((string) $ligne->value, true);

        if (json_last_error() !== JSON_ERROR_NONE || ! is_array($decode)) {
            return;
        }

        $donnees = [
            'value' => json_encode($transformation($decode), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ];

        if (Schema::hasColumn('settings', 'updated_at')) {
            $donnees['updated_at'] = now();
        }

        DB::table('settings')->where('key', self::KEY)->update($donnees);
    }
};
