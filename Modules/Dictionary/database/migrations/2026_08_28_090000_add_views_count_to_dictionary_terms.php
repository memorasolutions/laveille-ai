<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Le glossaire appelle ViewCounterService::record() depuis toujours (voir
 * PublicDictionaryController::show()), mais la colonne n'a jamais existé sur
 * dictionary_terms - le service reste un no-op silencieux (Schema::hasColumn
 * gardé côté service, garde-fou zéro casse). 502 fiches, aucune ne remonte
 * quelle est lue ; cette donnée sert déjà à cibler l'enrichissement plutôt
 * que de le faire au hasard (même logique que l'annuaire, clicks_count).
 *
 * Le tri anti-robot (config('view_counter.bot_patterns')) et la déduplication
 * courte fenêtre vivent déjà dans le service depuis l'incident 2026-08-13
 * (compteur des actualités : 1,1M cumulé contre 666 vues réelles) - cette
 * migration n'ajoute donc AUCUNE logique, seulement la colonne qui manquait.
 *
 * Pas de views_count_verified ici (contrairement à Tools/Authors/News, voir
 * leurs migrations du 2026-08-14) : ce compteur "propre" n'a de sens QUE
 * pour isoler un historique déjà pollué d'un nouveau départ filtré. Sur ce
 * module, views_count est filtré/dédupliqué dès sa toute première écriture -
 * il n'y a donc aucun historique à assainir, et un jumeau repartirait de
 * zéro en parfait lock-step avec lui, pour toujours (même incrément, même
 * garde, même appel, dans la même méthode). Un doublon sans risque réel de
 * divergence est justement ce que la règle DRY du projet interdit.
 */
return new class extends Migration
{
    public function up(): void
    {
        // La colonne PRE-EXISTE en production (constate le 2026-08-28 : la CI a
        // echoue sur « Duplicate column name 'views_count' »), alors qu'aucune
        // migration du depot ne la cree - elle y a donc ete ajoutee hors du
        // systeme de migrations. La base de developpement, elle, ne l'avait pas.
        // Sans cette garde, la migration echoue en prod et BLOQUE tous les
        // deploiements suivants, puisque la CI relance `migrate --force` a
        // chaque fois. C'est le prix a payer quand deux bases divergent : une
        // migration additive doit tolerer que sa cible existe deja.
        if (! Schema::hasColumn('dictionary_terms', 'views_count')) {
            Schema::table('dictionary_terms', function (Blueprint $table) {
                $table->unsignedInteger('views_count')->default(0)->after('sort_order');
                $table->index('views_count');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('dictionary_terms', 'views_count')) {
            return;
        }

        // Cette migration n'a PAS cree la colonne en production, elle l'y a
        // trouvee. Un rollback naif effacerait donc des vues reellement
        // comptees - une suppression de donnees, ce que ce projet interdit
        // sans exception. On ne retire donc la colonne QUE si elle est vide :
        // dans ce cas elle ne porte rien, et le retrait est reversible sans
        // perte. Sinon on s'arrete en le disant, plutot que de detruire en
        // silence ce qu'on est incapable de reconstituer.
        $cumul = (int) DB::table('dictionary_terms')->sum('views_count');

        if ($cumul > 0) {
            throw new RuntimeException(
                'Rollback refuse : la colonne views_count de dictionary_terms porte '
                .$cumul.' vues cumulees. Les retirer serait une perte de donnees. '
                .'Sauvegarder la colonne avant, puis retirer la garde de ce down() '
                .'si le retrait est vraiment voulu.'
            );
        }

        Schema::table('dictionary_terms', function (Blueprint $table) {
            $table->dropIndex(['views_count']);
            $table->dropColumn('views_count');
        });
    }
};
