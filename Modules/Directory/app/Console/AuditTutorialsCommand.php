<?php

declare(strict_types=1);

namespace Modules\Directory\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Modules\Directory\Services\YouTubeService;

class AuditTutorialsCommand extends Command
{
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
}
