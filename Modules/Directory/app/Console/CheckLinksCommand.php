<?php

declare(strict_types=1);

namespace Modules\Directory\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Modules\Directory\Models\Tool;

class CheckLinksCommand extends Command
{
    protected $signature = 'directory:check-links
                            {--fix : Met en quarantaine (status=draft) les fiches dont le lien est confirme disparu}
                            {--limit= : Nombre maximum de fiches a controler (sous-ensemble)}';

    protected $description = 'Vérifie les liens externes de tous les outils publiés et écrit le résultat sur chaque fiche.';

    /**
     * Codes qui signifient vraiment « la ressource n'existe plus ». C'est la SEULE famille
     * qui justifie une mise en quarantaine.
     *
     * Visibilité PUBLIC (et non private) depuis directory:check-lifecycle-consistency : cette
     * commande compare le lifecycle_status affiché sur une fiche au dernier code HTTP mesuré ici
     * et RÉUTILISE ce classement plutôt que de le redéfinir - une seule source de vérité sur ce
     * qu'un code HTTP veut dire pour ce projet (règle DRY, CLAUDE.md).
     */
    public const DISPARU = [404, 410];

    /**
     * Codes ambigus où un simple code HTTP ne suffit pas à distinguer un vrai arrêt d'un
     * pare-feu, d'un mur de paiement ou d'un service en train de démarrer. On y conserve un
     * extrait court du corps (voir extractBodyNote()) pour qu'un humain tranche.
     *
     * Visibilité PUBLIC : voir le commentaire de DISPARU ci-dessus, même raison.
     */
    public const AMBIGUS = [401, 402, 403, 503];

    /**
     * Pause entre deux fiches consécutives, pour éviter de déclencher une limitation de débit
     * quand plusieurs fiches pointent vers le même domaine (mesuré le 2026-09-13 : 131 fiches
     * pointant vers producthunt.com ont toutes répondu 429 par excès de cadence, pas parce que
     * l'outil était disparu).
     */
    private const PAUSE_MICROSECONDES = 300_000;

    public function handle(): int
    {
        $query = Tool::published();

        if ($limit = $this->option('limit')) {
            $query->limit((int) $limit);
        }

        $tools = $query->get();
        $total = $tools->count();
        $ok = 0;
        $redirects = 0;
        $disparus = 0;
        $refus = 0;
        $ennuis = 0;
        $quarantined = [];
        $rows = [];

        foreach ($tools as $index => $tool) {
            $url = $tool->url;

            if (! $url) {
                continue;
            }

            $streakAvant = (int) ($tool->url_failure_streak ?? 0);

            try {
                $response = Http::timeout(10)->withoutVerifying()->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (compatible; LaVeilleBot/1.0)',
                ])->get($url);

                $status = $response->status();
                $redirect = '';
                $statusDisplay = $status;
                $family = null;
                $note = null;

                if ($status >= 400) {
                    $family = $this->classify($status);
                    $statusDisplay = $this->statusLabel($status, $family);

                    if (in_array($status, self::AMBIGUS, true)) {
                        $note = $this->extractBodyNote($response->body());
                    }

                    // Avant de conclure a une disparition, on tente la variante evidente
                    // www./sans-www : cas reel et decisif mesure le 2026-09-13 - fusioo.com
                    // repond 404 alors que www.fusioo.com repond 200, l'outil est parfaitement
                    // vivant, seul le www manquait dans la fiche. On n'ecrase jamais l'URL tout
                    // seul : on le note pour qu'un humain tranche.
                    if ($status === 404) {
                        $note = $this->checkWwwVariant($url) ?? $note;
                    }

                    if ($family === 'disparu') {
                        $disparus++;

                        if ($this->option('fix')) {
                            $tool->update(['status' => 'draft']);
                            $quarantined[] = $tool->name;
                            $this->error("{$tool->name} → {$status} (disparu, mis en quarantaine)");
                        } else {
                            $this->error("{$tool->name} → {$status} (disparu, --fix absent : aucune action)");
                        }
                    } elseif ($family === 'refus') {
                        $refus++;
                        $this->warn("{$tool->name} → {$status} (le site refuse le robot, probablement vivant : aucune action)");
                    } else {
                        $ennuis++;
                        $this->warn("{$tool->name} → {$status} (ennui serveur probablement transitoire : aucune action)");
                    }
                } elseif ($status >= 300 && $status < 400) {
                    $redirect = $response->header('Location') ?? '';
                    $redirects++;
                    $this->warn("{$tool->name} → {$status} → {$redirect}");
                } else {
                    $ok++;
                }

                $rows[] = [$tool->name, $url, $statusDisplay, $redirect];

                $this->recordCheckResult($tool->id, (string) $status, $note, $family, $streakAvant, aRepondu: true);
            } catch (\Throwable $e) {
                $ennuis++;
                $motif = $this->classifyConnectionFailure($e);
                $statusDisplay = $this->statusLabel($motif, 'ennui');
                $rows[] = [$tool->name, $url, $statusDisplay, $e->getMessage()];
                $this->warn("{$tool->name} → {$motif} (ennui serveur probablement transitoire : aucune action)");

                $this->recordCheckResult($tool->id, $motif, null, null, $streakAvant, aRepondu: false);
            }

            if ($index < $total - 1) {
                usleep(self::PAUSE_MICROSECONDES);
            }
        }

        $this->table(['Nom', 'URL', 'Status', 'Redirect'], $rows);
        $this->info("OK: {$ok} | Redirects: {$redirects} | Disparus (404/410): {$disparus} | Refus du robot mais vivants: {$refus} | Ennuis serveur transitoires: {$ennuis}");

        if ($disparus === 0) {
            $this->info('Aucune fiche disparue : aucune quarantaine à appliquer.');
        } elseif ($this->option('fix')) {
            $noms = implode(', ', $quarantined);
            $this->info("Mises en quarantaine (statut=draft) : {$noms}");
        } else {
            $this->info("{$disparus} fiche(s) disparue(s) détectée(s), aucune quarantaine appliquée (relancer avec --fix pour agir).");
        }

        return self::SUCCESS;
    }

    /**
     * Écrit le résultat du contrôle sur la fiche, via le CONSTRUCTEUR DE REQUÊTES
     * (DB::table()->update()) et jamais Eloquent save(), pour ne pas faire avancer updated_at
     * d'une fiche dont le contenu éditorial n'a pas changé - convention déjà appliquée ailleurs
     * dans ce dépôt (voir ViewCounterService::record() et la migration content_updated_at).
     *
     * url_failure_streak : incrémenté sur un échec FRANC (famille "disparu" seulement), remis à
     * 0 dès que l'adresse répond (peu importe le code - succès, redirection, refus ou ennui avec
     * réponse HTTP réelle), et laissé INCHANGÉ en l'absence totale de réponse (TIMEOUT/DNS), faute
     * de savoir si la ressource est vraiment jointe.
     */
    private function recordCheckResult(int $toolId, string $status, ?string $note, ?string $family, int $streakAvant, bool $aRepondu): void
    {
        if ($family === 'disparu') {
            $streakApres = $streakAvant + 1;
        } elseif ($aRepondu) {
            $streakApres = 0;
        } else {
            $streakApres = $streakAvant;
        }

        DB::table('directory_tools')->where('id', $toolId)->update([
            'url_last_checked_at' => now(),
            'url_last_status' => $status,
            'url_last_note' => $note,
            'url_failure_streak' => $streakApres,
        ]);
    }

    /**
     * Tente la variante évidente (ajout ou retrait du préfixe www.) d'une URL en 404, et renvoie
     * une note courte si elle répond (ex. « variante www répond 200 »). N'écrase jamais l'URL de
     * la fiche : c'est une observation pour qu'un humain tranche, pas une correction automatique.
     */
    private function checkWwwVariant(string $url): ?string
    {
        $alternate = $this->alternateWwwUrl($url);

        if ($alternate === null) {
            return null;
        }

        try {
            $response = Http::timeout(10)->withoutVerifying()->withHeaders([
                'User-Agent' => 'Mozilla/5.0 (compatible; LaVeilleBot/1.0)',
            ])->get($alternate);

            $status = $response->status();

            if ($status < 400) {
                return "variante www répond {$status}";
            }
        } catch (\Throwable) {
            // Meilleur effort : la variante ne repond pas non plus, on ne note rien de plus.
        }

        return null;
    }

    /**
     * Construit l'URL avec le préfixe www. ajouté (s'il est absent) ou retiré (s'il est présent),
     * en conservant schéma/port/chemin/requête/fragment. Renvoie null si l'URL n'est pas
     * analysable ou si aucune variante distincte n'existe.
     */
    private function alternateWwwUrl(string $url): ?string
    {
        $parts = parse_url($url);

        if (! isset($parts['scheme'], $parts['host'])) {
            return null;
        }

        $host = $parts['host'];
        $alternateHost = str_starts_with($host, 'www.') ? substr($host, 4) : 'www.'.$host;

        if ($alternateHost === '' || $alternateHost === $host) {
            return null;
        }

        $alternate = $parts['scheme'].'://'.$alternateHost;

        if (isset($parts['port'])) {
            $alternate .= ':'.$parts['port'];
        }

        $alternate .= $parts['path'] ?? '';

        if (isset($parts['query'])) {
            $alternate .= '?'.$parts['query'];
        }

        if (isset($parts['fragment'])) {
            $alternate .= '#'.$parts['fragment'];
        }

        return $alternate;
    }

    /**
     * Extrait un court passage lisible du corps de reponse (balises retirees, espaces multiples
     * compactes, 150 caracteres maximum) pour un code HTTP ambigu (401/402/403/503). C'est ce qui
     * distingue un vrai arret ("Payment required / DEPLOYMENT_DISABLED", "walls.sh is private")
     * d'un pare-feu qui n'empeche pas grand-chose ("Just a moment..." = defi Cloudflare, donc
     * vivant ; {"status": "warming_up"} = service en train de demarrer, donc vivant).
     */
    private function extractBodyNote(string $body): ?string
    {
        // Plafond avant nettoyage : evite de faire tourner strip_tags()/preg_replace() sur un
        // corps de plusieurs megaoctets pour n'en garder au final que 150 caracteres.
        $texte = strip_tags(substr($body, 0, 2000));
        $texte = trim((string) preg_replace('/\s+/', ' ', $texte));

        if ($texte === '') {
            return null;
        }

        return substr($texte, 0, 150);
    }

    /**
     * Distingue une absence de reponse ou le domaine resout mais ne repond pas (TIMEOUT) d'une
     * absence de reponse ou le DNS ne resout plus du tout (DNS) - mesure du 2026-09-13 : ce sont
     * deux populations differentes (24 vs 15 sur les fiches controlees a la main), la seconde
     * etant des morts quasi certains.
     */
    private function classifyConnectionFailure(\Throwable $e): string
    {
        $message = mb_strtolower($e->getMessage());

        if (str_contains($message, 'could not resolve host')
            || str_contains($message, 'name or service not known')
            || str_contains($message, 'nodename nor servname')
            || str_contains($message, 'server ip address could not be found')) {
            return 'DNS';
        }

        return 'TIMEOUT';
    }

    /**
     * Classe un code HTTP d'échec (>= 400) dans l'une des trois familles. Seul « disparu »
     * justifie une action ; « refus » et « ennui » ne sont que des signalements.
     *
     * Tout code 4xx qui n'est pas dans DISPARU tombe dans la famille « refus » (le site est
     * vivant mais refuse notre robot, User-Agent LaVeilleBot/1.0 étant une signature évidente).
     * Les codes 401, 403, 405, 429 en sont les cas les plus fréquents, mais un autre code 4xx
     * non listé ici reste un refus, jamais une disparition : on n'agit QUE sur DISPARU.
     */
    private function classify(int $status): string
    {
        if (in_array($status, self::DISPARU, true)) {
            return 'disparu';
        }

        if ($status >= 400 && $status < 500) {
            return 'refus';
        }

        return 'ennui';
    }

    private function statusLabel(int|string $status, string $family): string
    {
        $label = match ($family) {
            'disparu' => 'disparu',
            'refus' => 'refus du robot, vivant',
            default => 'ennui serveur',
        };

        return "{$status} ({$label})";
    }
}
