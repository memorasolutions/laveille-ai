<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Modules\Dictionary\Models\Term;

/**
 * Relation broader/narrower Docker <-> Socket (2026-07-26), recommandation de l'audit de maillage
 * interne de l'article "Comment installer OpenClaw en toute sécurité sur macOS ?" (id=67) : le
 * guide explique explicitement le risque du socket Docker (docker.sock) comme mécanisme central de
 * la fiche "Docker", et la fiche "Socket" gagne en retour un lien remontant vers un concept parent
 * concret et déjà documenté (docker.sock).
 *
 * Docker (slug docker) devient narrower de Socket via narrower_slugs=["socket"] sur Docker.
 * Socket (slug socket) devient broader de Docker via broader_slugs=["docker"] sur Socket.
 *
 * Note : les migrations "add_glossary_term_docker" (2026-07-25) et "add_glossary_term_socket"
 * (2026-07-26) avaient toutes deux laissé broader_slugs/narrower_slugs vides à la création (aucune
 * relation identifiée à ce moment) - cette migration comble cet écart a posteriori, par slug (les
 * ids numériques diffèrent entre environnements local/prod selon l'ordre d'insertion réel, jamais
 * fiables comme clé de jointure inter-environnements).
 *
 * Idempotente : n'écrase que si le slug narrower/broader n'est pas déjà présent dans le tableau
 * JSON existant (évite les doublons si la migration est rejouée ou si une relation manuelle a déjà
 * été ajoutée entre-temps). Réversible : down() retire précisément "socket" de narrower_slugs sur
 * Docker et "docker" de broader_slugs sur Socket, sans toucher aux autres entrées de ces tableaux.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }
        if (! class_exists(Term::class)) {
            return;
        }

        $docker = Term::where('slug->fr_CA', 'docker')->first();
        if ($docker) {
            $narrower = $docker->narrower_slugs ?? [];
            if (! in_array('socket', $narrower, true)) {
                $narrower[] = 'socket';
                $docker->narrower_slugs = array_values($narrower);
                $docker->save();
                echo "[glossaire] docker.narrower_slugs += socket\n";
            } else {
                echo "[glossaire] docker.narrower_slugs contient déjà socket, skip\n";
            }
        } else {
            echo "[glossaire] terme docker introuvable, skip\n";
        }

        $socket = Term::where('slug->fr_CA', 'socket')->first();
        if ($socket) {
            $broader = $socket->broader_slugs ?? [];
            if (! in_array('docker', $broader, true)) {
                $broader[] = 'docker';
                $socket->broader_slugs = array_values($broader);
                $socket->save();
                echo "[glossaire] socket.broader_slugs += docker\n";
            } else {
                echo "[glossaire] socket.broader_slugs contient déjà docker, skip\n";
            }
        } else {
            echo "[glossaire] terme socket introuvable, skip\n";
        }
    }

    public function down(): void
    {
        if (! class_exists(Term::class)) {
            return;
        }

        $docker = Term::where('slug->fr_CA', 'docker')->first();
        if ($docker) {
            $docker->narrower_slugs = array_values(array_diff($docker->narrower_slugs ?? [], ['socket']));
            $docker->save();
        }

        $socket = Term::where('slug->fr_CA', 'socket')->first();
        if ($socket) {
            $socket->broader_slugs = array_values(array_diff($socket->broader_slugs ?? [], ['docker']));
            $socket->save();
        }
    }
};
