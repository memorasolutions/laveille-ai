<?php

declare(strict_types=1);

namespace Modules\FileAttente\Services;

use Illuminate\Contracts\Session\Session;

/**
 * Quota de création de tableaux PAR SESSION (spec 6.6) : jamais par IP, car une classe entière
 * partage la même adresse (leçon #3103). Les horodatages vivent dans la session de l'animateur.
 * Une limite inférieure à 1 désactive le quota.
 *
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 */
class CreationQuota
{
    public const KEY = 'fa.created_at';

    private const WINDOW_SECONDS = 3600;

    public function limit(): int
    {
        return (int) config('file-attente.boards_per_hour', 20);
    }

    public function allows(Session $session): bool
    {
        $limit = $this->limit();

        return $limit < 1 || count($this->recent($session)) < $limit;
    }

    public function record(Session $session): void
    {
        $recent = $this->recent($session);
        $recent[] = time();
        $session->put(self::KEY, $recent);
    }

    /** @return list<int> */
    private function recent(Session $session): array
    {
        $since = time() - self::WINDOW_SECONDS;

        return array_values(array_filter(
            (array) $session->get(self::KEY, []),
            static fn ($t): bool => is_int($t) && $t > $since,
        ));
    }
}
