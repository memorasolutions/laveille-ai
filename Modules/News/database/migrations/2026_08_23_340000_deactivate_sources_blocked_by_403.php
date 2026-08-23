<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

/**
 * Désactive les quatre sources qui refusent l'adresse IP du serveur (2026-08-23).
 *
 * PREUVE HORODATÉE, tirée du canal `news_fetch` déployé le jour même. Les quatre renvoient un
 * **403** à chaque tentative, y compris APRÈS le déploiement de l'identité de navigateur :
 *   17:15:25  #13 AI News                 403
 *   17:15:49  #17 Le Big Data             403
 *   17:16:58  #29 ZDNet France            403
 *   17:19:06  #46 The Atlantic Technology 403
 * Les MÊMES flux répondent 20 éléments depuis un poste ordinaire, avec le même code et la même
 * librairie. Ce n'est donc pas l'identité annoncée qui est refusée, c'est l'ADRESSE de
 * l'hébergement mutualisé. Rien de ce qui est en notre pouvoir depuis ce serveur n'y changera.
 *
 * POURQUOI MAINTENANT, et pas plus tôt : la version précédente trie les sources par FAMINE, les
 * jamais-récoltées d'abord. Sans cette désactivation, chaque passage commencerait donc par les
 * trois sources qui ne peuvent JAMAIS aboutir, et leur donnerait le budget de temps d'un
 * processus qui est déjà interrompu au bout de deux minutes. Le tri par famine et cette
 * désactivation forment une seule et même correction : garder les bloquées actives aurait
 * transformé une bonne idée en régression.
 *
 * DÉSACTIVÉES, JAMAIS SUPPRIMÉES. Leurs articles déjà collectés restent rattachés, et down()
 * rend l'état exact d'avant. Le jour où un relais est mis en place, il suffit de les réactiver.
 *
 * Le cas de « Le Big Data » mérite d'être nommé : c'était la MEILLEURE source du site, 77 % de
 * taux de publication. Elle est muette depuis le 7 juillet. La désactiver ne perd rien - elle
 * ne rapportait déjà plus rien - mais rend la vérité visible au lieu de la laisser se déguiser
 * en source active.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Log;
use Modules\News\Models\NewsSource;

return new class extends Migration
{
    /** Identifiant => libellé, pour que le journal soit lisible sans ouvrir la base. */
    private const BLOQUEES_403 = [
        13 => 'AI News',
        17 => 'Le Big Data',
        29 => 'ZDNet France',
        46 => 'The Atlantic Technology',
    ];

    public function up(): void
    {
        foreach (self::BLOQUEES_403 as $id => $libelle) {
            $source = NewsSource::find($id);

            if ($source === null) {
                Log::channel('news_fetch')->warning("[sources] source {$id} ({$libelle}) introuvable : desactivation ignoree.");

                continue;
            }

            // Comparaison NON stricte : la colonne peut remonter un entier selon le pilote.
            if (! $source->active) {
                continue;
            }

            $source->active = false;
            $source->save();
        }
    }

    public function down(): void
    {
        foreach (array_keys(self::BLOQUEES_403) as $id) {
            $source = NewsSource::find($id);

            if ($source !== null) {
                $source->active = true;
                $source->save();
            }
        }
    }
};
