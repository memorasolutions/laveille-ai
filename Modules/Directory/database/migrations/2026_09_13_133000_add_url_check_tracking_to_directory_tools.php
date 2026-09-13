<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// ACTION: migration additive - trace le RESULTAT du dernier controle de lien externe
// (directory:check-links) directement sur la fiche, meme convention que
// screenshot_last_attempt_at/screenshot_last_attempt_result et education_last_checked_at.
// Avant ce correctif, directory:check-links existait deja et classait correctement les echecs
// (disparu vs refus du robot vs ennui serveur, voir CheckLinksCommand::classify()), mais son
// resultat s'affichait en console puis disparaissait : rien n'etait planifie (aucune entree dans
// routes/console.php ni cron serveur) et rien n'etait ecrit sur la fiche, donc le travail etait
// refait a zero a chaque execution manuelle.
// MCP: SELF (migration < 5 lignes de logique, meme convention que les migrations screenshot_* voisines)
// RAISON: mesure manuelle du 2026-09-13 sur les 2233 fiches actives et publiees - entre autres
// 129 faux positifs de cadence (429 puis 200 en mesure lente) et 51 refus Cloudflare quasi tous
// vivants (dont ChatGPT, Claude, Midjourney, Perplexity) qu'une seule tentative brute confondrait
// a tort avec une vraie disparition.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('directory_tools', function (Blueprint $table): void {
            if (! Schema::hasColumn('directory_tools', 'url_last_checked_at')) {
                $table->timestamp('url_last_checked_at')->nullable()->after('url');
            }

            if (! Schema::hasColumn('directory_tools', 'url_last_status')) {
                // Code HTTP (ex. '200', '404') ou un motif de connexion sans reponse ('TIMEOUT', 'DNS').
                $table->string('url_last_status', 20)->nullable()->after('url_last_checked_at');
            }

            if (! Schema::hasColumn('directory_tools', 'url_last_note')) {
                // Extrait court (<=150 caracteres, balises retirees) du corps de reponse pour un
                // code ambigu (401/402/403/503 : defi Cloudflare, deploiement desactive, service
                // prive, service en demarrage...), ou une note "variante www repond XXX" quand un
                // 404 se resout par le simple ajout/retrait du prefixe www.
                $table->string('url_last_note', 255)->nullable()->after('url_last_status');
            }

            if (! Schema::hasColumn('directory_tools', 'url_failure_streak')) {
                // Nombre d'echecs FRANCS consecutifs (famille "disparu" = 404/410 seulement, voir
                // CheckLinksCommand::DISPARU). Un refus du robot ou un ennui serveur ne l'incremente
                // jamais ; il retombe a 0 des que l'adresse repond (succes, redirection, refus ou
                // ennui avec reponse HTTP reelle) - seule l'absence totale de reponse (TIMEOUT/DNS)
                // le laisse inchange, faute de savoir si la ressource est vraiment jointe.
                $table->unsignedInteger('url_failure_streak')->default(0)->after('url_last_note');
            }
        });
    }

    public function down(): void
    {
        Schema::table('directory_tools', function (Blueprint $table): void {
            foreach (['url_last_checked_at', 'url_last_status', 'url_last_note', 'url_failure_streak'] as $column) {
                if (Schema::hasColumn('directory_tools', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
