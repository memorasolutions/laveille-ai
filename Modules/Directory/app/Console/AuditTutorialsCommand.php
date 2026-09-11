<?php

declare(strict_types=1);

namespace Modules\Directory\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;
use Modules\Core\Traits\RotatesCommandBackups;
use Modules\Directory\Services\YouTubeService;

class AuditTutorialsCommand extends Command
{
    use RotatesCommandBackups;

    protected $signature = 'tools:audit-tutorials {--fix : désapprouve les tutos non conformes} {--email= : adresse d\'alerte}';

    protected $description = 'Audite les tutoriels approuvés (langue FR/EN + pertinence) et alerte/corrige';

    public function handle(): int
    {
        $resources = DB::table('directory_resources')
            ->where('is_approved', 1)
            ->where('type', 'youtube')
            ->select('id', 'directory_tool_id', 'title')
            ->get();

        $bad = $resources->filter(fn ($r) => ! YouTubeService::titleIsAcceptable((string) $r->title))->values();

        $this->info("Audit : {$bad->count()} tutoriel(s) non conforme(s) sur {$resources->count()} approuvés.");

        if ($bad->isEmpty()) {
            // ACTION : le bilan s'ecrit AUSSI quand tout va bien.
            // MCP: SELF (<5 lignes)
            // RAISON: ticket #2436 - ce retour anticipe sortait AVANT tout envoi, donc « zero non
            // conforme » etait indiscernable de « la commande n'a jamais tourne ». Un zero
            // rassurant sans trace n'est pas un controle, c'est un silence.
            $this->ecrireBilan($resources->count(), 0, 0, []);

            return self::SUCCESS;
        }

        $samples = $bad->take(30)->pluck('title')->all();
        foreach ($samples as $t) {
            $this->line("  - {$t}");
        }

        $fixed = 0;
        if ($this->option('fix') && $bad->count() <= 200) {
            $fixed = DB::table('directory_resources')->whereIn('id', $bad->pluck('id'))->update(['is_approved' => 0]);
            $this->info("{$fixed} désapprouvé(s).");
        }

        $this->ecrireBilan($resources->count(), $bad->count(), $fixed, $samples);

        $email = $this->option('email') ?: config('mail.from.address') ?: 'stephane@memora.ca';
        $body = "Audit des tutoriels de l'annuaire (langue FR/EN + pertinence).\n\n"
            . "- Non conformes détectés : {$bad->count()}\n"
            . "- Désapprouvés automatiquement : {$fixed}\n\n"
            . "Exemples (max 30) :\n" . implode("\n", array_map(fn ($t) => "• {$t}", $samples));

        try {
            Mail::raw($body, function ($message) use ($email, $bad) {
                $message->to($email)->subject("[Annuaire] Audit tutoriels : {$bad->count()} non conformes");
            });
        } catch (\Throwable $e) {
            Log::error('Échec envoi alerte audit tutoriels', ['error' => $e->getMessage()]);
        }

        return self::SUCCESS;
    }

    /**
     * Ecrit et journalise le bilan de l'audit.
     *
     * Ce bilan ne partait QUE par courriel : personne ne pouvait etablir l'etat du systeme
     * sans fouiller une boite de reception, et c'est pour cette raison que le compte
     * approuves contre desapprouves est reste « non etabli » dans deux mandats successifs.
     *
     * L'ecriture ne fait JAMAIS echouer l'audit : un bilan manquant est un defaut
     * d'observabilite, pas une raison de faire tomber une tache planifiee qui, elle, a fait
     * son travail. L'echec est journalise et la commande poursuit.
     *
     * La rotation borne l'accumulation, exactement comme pour les sauvegardes de commande
     * (Modules\Core\Traits\RotatesCommandBackups, ticket #2434).
     *
     * @param  array<array-key, mixed>  $exemples
     */
    private function ecrireBilan(int $approuves, int $nonConformes, int $corriges, array $exemples): ?string
    {
        $maintenant = now('America/Toronto');

        $bilan = [
            'horodatage' => $maintenant->toIso8601String(),
            'approuves' => $approuves,
            'non_conformes' => $nonConformes,
            'corriges' => $corriges,
            'exemples' => array_slice($exemples, 0, 30),
        ];

        $chemin = storage_path('app/audit-tutorials-'.$maintenant->format('Ymd-His').'.json');

        try {
            File::put($chemin, json_encode($bilan, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            $this->rotateCommandBackups(storage_path('app/audit-tutorials-*.json'), 30);
        } catch (\Throwable $e) {
            Log::error('[AuditTutorials] bilan non ecrit sur disque', ['chemin' => $chemin, 'error' => $e->getMessage()]);
            $chemin = null;
        }

        Log::info('[AuditTutorials] bilan', [
            'approuves' => $approuves,
            'non_conformes' => $nonConformes,
            'corriges' => $corriges,
            'nombre_exemples' => count($bilan['exemples']),
            'chemin' => $chemin,
        ]);

        return $chemin;
    }
}
