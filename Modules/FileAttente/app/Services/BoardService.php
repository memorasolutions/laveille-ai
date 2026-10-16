<?php

declare(strict_types=1);

namespace Modules\FileAttente\Services;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Modules\FileAttente\Exceptions\StaleBoardVersionException;
use Modules\FileAttente\Models\Board;
use Modules\FileAttente\Models\Participant;

/**
 * Logique d'un tableau de file d'attente (décisions v0.2, docs/specs/2026-10-16-file-attente-qr-design.md).
 *
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 */
class BoardService
{
    public const GAP = 1024;

    private const PSEUDOS = [
        'Castor', 'Renard', 'Loutre', 'Hibou', 'Orignal', 'Lynx', 'Huard', 'Caribou',
        'Faucon', 'Belette', 'Marmotte', 'Bison',
    ];

    /**
     * @param  list<string>  $names
     * @param  array{title?: ?string, mask_mode?: string, scan_mode?: string, allow_self_leave?: bool}  $options
     * @return array{board: Board, manage_token: string, display_token: string, participants: list<array{participant: Participant, token: string}>}
     */
    public function createBoard(array $names, array $options = []): array
    {
        $maxLen = (int) config('file-attente.max_name_len', 40);
        $clean = [];
        foreach ($names as $name) {
            $name = trim((string) preg_replace('/\s+/u', ' ', strip_tags((string) $name)));
            if ($name !== '') {
                $clean[] = mb_substr($name, 0, $maxLen);
            }
        }
        if (count($clean) > (int) config('file-attente.max_participants', 60)) {
            throw new InvalidArgumentException(__('fileattente::ui.errors.too_many_in_board'));
        }

        $maskMode = $options['mask_mode'] ?? 'initial';
        if (! in_array($maskMode, ['initial', 'pseudo', 'numero'], true)) {
            throw new InvalidArgumentException('mask_mode invalide.');
        }
        $scanMode = $options['scan_mode'] ?? 'borne';
        if (! in_array($scanMode, ['borne', 'telephone'], true)) {
            throw new InvalidArgumentException('scan_mode invalide.');
        }

        $manage = Str::random(48);
        $display = Str::random(48);

        return DB::transaction(function () use ($clean, $options, $maskMode, $scanMode, $manage, $display): array {
            $board = Board::create([
                'manage_token_hash' => $this->hash($manage),
                'display_token_hash' => $this->hash($display),
                'display_token_cipher' => Crypt::encryptString($display),
                'title' => isset($options['title']) ? mb_substr(trim((string) $options['title']), 0, 120) ?: null : null,
                'version' => 0,
                'mask_mode' => $maskMode,
                'scan_mode' => $scanMode,
                'allow_self_leave' => (bool) ($options['allow_self_leave'] ?? true),
                'last_activity_at' => now(),
                'purge_after' => now()->addHours((int) config('file-attente.purge_hours', 24)),
            ]);

            $out = [];
            foreach ($clean as $i => $name) {
                $out[] = $this->makeParticipant($board, $name, $i + 1, Participant::PENDING);
            }

            Cache::forever($this->cacheKey($board), 0);

            return [
                'board' => $board,
                'manage_token' => $manage,
                'display_token' => $display,
                'participants' => $out,
            ];
        });
    }

    public function join(Board $b, string $plainToken): Participant
    {
        $p = Participant::where('board_id', $b->id)->where('token_hash', $this->hash($plainToken))->firstOrFail();

        DB::transaction(function () use ($b, $p): void {
            $this->lockBoard($b);
            $affected = Participant::where('id', $p->id)
                ->whereIn('status', [Participant::PENDING, Participant::REMOVED])
                ->update([
                    'status' => Participant::IN_LIST,
                    'sort_key' => $this->maxSortKey($b) + self::GAP,
                    'joined_at' => now(),
                    'updated_at' => now(),
                ]);
            if ($affected > 0) {
                $this->bumpVersion($b);
            }
        });

        return $p->refresh();
    }

    public function leave(Board $b, string $plainToken): void
    {
        if (! $b->allow_self_leave) {
            return;
        }
        $p = Participant::where('board_id', $b->id)->where('token_hash', $this->hash($plainToken))->firstOrFail();

        DB::transaction(function () use ($b, $p): void {
            $affected = Participant::where('id', $p->id)
                ->whereIn('status', [Participant::IN_LIST, Participant::CALLED])
                ->update(['status' => Participant::REMOVED, 'sort_key' => null, 'updated_at' => now()]);
            if ($affected > 0) {
                $this->bumpVersion($b);
            }
        });
    }

    public function animatorAdd(Board $b, string $name): Participant
    {
        $name = mb_substr(trim((string) preg_replace('/\s+/u', ' ', strip_tags($name))), 0, (int) config('file-attente.max_name_len', 40));
        if ($name === '') {
            throw new InvalidArgumentException(__('fileattente::ui.errors.empty_name'));
        }

        return DB::transaction(function () use ($b, $name): Participant {
            $this->lockBoard($b);
            if ($b->participants()->count() >= (int) config('file-attente.max_participants', 60)) {
                throw new InvalidArgumentException(__('fileattente::ui.errors.too_many_in_board'));
            }
            $seq = (int) $b->participants()->max('joined_seq') + 1;
            $made = $this->makeParticipant($b, $name, $seq, Participant::IN_LIST);
            $p = $made['participant'];
            $p->forceFill(['sort_key' => $this->maxSortKey($b, $p->id) + self::GAP, 'joined_at' => now()])->save();
            $this->bumpVersion($b);

            return $p;
        });
    }

    /** L'animateur marque une personne « pas encore arrivée » comme arrivée : elle entre en fin de file. */
    public function animatorArrive(Board $b, Participant $p): void
    {
        DB::transaction(function () use ($b, $p): void {
            $this->lockBoard($b);
            $affected = Participant::where('id', $p->id)->where('board_id', $b->id)
                ->where('status', Participant::PENDING)
                ->update([
                    'status' => Participant::IN_LIST,
                    'sort_key' => $this->maxSortKey($b) + self::GAP,
                    'joined_at' => now(),
                    'updated_at' => now(),
                ]);
            if ($affected > 0) {
                $this->bumpVersion($b);
            }
        });
    }

    public function animatorRemove(Board $b, Participant $p): void
    {
        DB::transaction(function () use ($b, $p): void {
            $affected = Participant::where('id', $p->id)->where('board_id', $b->id)
                ->whereIn('status', [Participant::PENDING, Participant::IN_LIST, Participant::CALLED])
                ->update(['status' => Participant::REMOVED, 'sort_key' => null, 'updated_at' => now()]);
            if ($affected > 0) {
                $this->bumpVersion($b);
            }
        });
    }

    public function callNext(Board $b): ?Participant
    {
        return DB::transaction(function () use ($b): ?Participant {
            $head = $this->ordered($b)->where('status', Participant::IN_LIST)->first();
            if ($head === null) {
                return null;
            }
            $head->forceFill(['status' => Participant::CALLED])->save();
            $this->bumpVersion($b);

            return $head;
        });
    }

    /**
     * @param  ?int  $afterSortKey  sort_key de la personne après laquelle placer $p; null = en tête.
     *
     * @throws StaleBoardVersionException
     */
    public function reorder(Board $b, Participant $p, ?int $afterSortKey, int $expectedVersion): void
    {
        DB::transaction(function () use ($b, $p, $afterSortKey, $expectedVersion): void {
            $this->lockBoard($b);
            if ($expectedVersion !== (int) $b->version) {
                throw new StaleBoardVersionException('Version périmée du tableau.');
            }

            $others = $this->ordered($b)
                ->whereIn('status', [Participant::IN_LIST, Participant::CALLED])
                ->where('id', '!=', $p->id)
                ->values();

            $idx = -1; // -1 = en tête
            if ($afterSortKey !== null) {
                $found = $others->search(fn (Participant $o): bool => (int) $o->sort_key === $afterSortKey);
                $idx = $found === false ? $others->count() - 1 : (int) $found;
            }
            $prev = $idx >= 0 ? (int) $others[$idx]->sort_key : 0;
            $next = $idx + 1 < $others->count() ? (int) $others[$idx + 1]->sort_key : null;

            if ($next === null) {
                $key = $prev + self::GAP;
            } else {
                $key = intdiv($prev + $next, 2);
                if ($next - $prev < 2 || $key <= $prev || $key >= $next) {
                    $key = null;
                }
            }

            if ($key !== null) {
                Participant::where('id', $p->id)->update(['sort_key' => $key, 'updated_at' => now()]);
            } else {
                // Écart épuisé : renumérotation espacée avec $p inséré à sa place.
                $ids = $others->pluck('id')->all();
                array_splice($ids, $idx + 1, 0, [$p->id]);
                foreach ($ids as $i => $id) {
                    Participant::where('id', $id)->update(['sort_key' => ($i + 1) * self::GAP]);
                }
            }

            $this->bumpVersion($b);
        });
    }

    public function bumpVersion(Board $b): void
    {
        Board::where('id', $b->id)->update(['version' => DB::raw('version + 1'), 'last_activity_at' => now()]);
        $b->refresh();
        Cache::forever($this->cacheKey($b), (int) $b->version);
    }

    /**
     * État PUBLIC : jamais full_name.
     *
     * @return array{version: int, title: ?string, items: list<array{position: int, label: string, status: string}>}
     */
    public function publicState(Board $b): array
    {
        $items = [];
        $pos = 0;
        foreach ($this->ordered($b)->whereIn('status', [Participant::IN_LIST, Participant::CALLED]) as $p) {
            $items[] = ['position' => ++$pos, 'label' => $p->display_label, 'status' => $p->status];
        }

        return ['version' => (int) $b->version, 'title' => $b->title, 'items' => $items];
    }

    /**
     * Jeton en clair, déchiffré à la demande (réimpression des QR). Jamais stocké en clair.
     *
     * @throws \RuntimeException si le jeton n'est pas récupérable (participant antérieur à token_cipher, clé changée)
     */
    public function plainToken(Participant $p): string
    {
        if ($p->token_cipher === null || $p->token_cipher === '') {
            throw new \RuntimeException(__('fileattente::ui.errors.token_unrecoverable'));
        }
        try {
            $token = Crypt::decryptString($p->token_cipher);
        } catch (DecryptException) {
            throw new \RuntimeException(__('fileattente::ui.errors.token_unrecoverable'));
        }
        if ($token === '') {
            throw new \RuntimeException(__('fileattente::ui.errors.token_unrecoverable'));
        }

        return $token;
    }

    /**
     * Jeton d'affichage en clair, déchiffré à la demande (page de gestion uniquement). Jamais stocké en clair.
     *
     * @throws \RuntimeException si le jeton n'est pas récupérable (tableau antérieur à la migration, clé changée)
     */
    public function displayPlainToken(Board $b): string
    {
        $cipher = $b->display_token_cipher;
        if ($cipher === null || $cipher === '') {
            throw new \RuntimeException(__('fileattente::ui.manage.display_link_unavailable'));
        }
        try {
            $token = Crypt::decryptString($cipher);
        } catch (DecryptException) {
            throw new \RuntimeException(__('fileattente::ui.manage.display_link_unavailable'));
        }
        if ($token === '') {
            throw new \RuntimeException(__('fileattente::ui.manage.display_link_unavailable'));
        }

        return $token;
    }

    /** Annule un « appelé » (fenêtre d'annulation de 5 s côté interface) : la personne retrouve sa place. */
    public function undoCall(Board $b, Participant $p): void
    {
        DB::transaction(function () use ($b, $p): void {
            $affected = Participant::where('id', $p->id)->where('board_id', $b->id)
                ->where('status', Participant::CALLED)
                ->update(['status' => Participant::IN_LIST, 'updated_at' => now()]);
            if ($affected > 0) {
                $this->bumpVersion($b);
            }
        });
    }

    /**
     * État côté ANIMATEUR (jeton de gestion) : full_name admis ici, jamais sur une route publique.
     *
     * @return array{version: int, title: ?string, items: list<array<string, mixed>>, pending: list<array<string, mixed>>}
     */
    public function animatorState(Board $b): array
    {
        $items = [];
        $pending = [];
        $pos = 0;
        foreach ($this->ordered($b) as $p) {
            if (in_array($p->status, [Participant::IN_LIST, Participant::CALLED], true)) {
                $items[] = [
                    'id' => $p->id, 'position' => ++$pos, 'sort_key' => $p->sort_key,
                    'full_name' => $p->full_name, 'label' => $p->display_label, 'status' => $p->status,
                ];
            } elseif ($p->status === Participant::PENDING) {
                $pending[] = ['id' => $p->id, 'full_name' => $p->full_name, 'label' => $p->display_label];
            }
        }

        return ['version' => (int) $b->version, 'title' => $b->title, 'items' => $items, 'pending' => $pending];
    }

    /**
     * État côté PARTICIPANT : son propre statut et son rang, jamais le nom d'un autre.
     *
     * @return array{version: int, status: string, label: string, position: ?int, ahead: ?int, allow_self_leave: bool}
     */
    public function participantState(Board $b, Participant $p): array
    {
        $position = null;
        $pos = 0;
        foreach ($this->ordered($b)->whereIn('status', [Participant::IN_LIST, Participant::CALLED]) as $o) {
            $pos++;
            if ($o->id === $p->id) {
                $position = $pos;
                break;
            }
        }

        return [
            'version' => (int) $b->version,
            'status' => $p->status,
            'label' => $p->display_label,
            'position' => $position,
            'ahead' => $position === null ? null : $position - 1,
            'allow_self_leave' => (bool) $b->allow_self_leave,
        ];
    }

    public function hash(string $plain): string
    {
        return hash('sha256', $plain);
    }

    public function cacheKey(Board $b): string
    {
        return "fa.board.{$b->id}.v";
    }

    /** @return \Illuminate\Support\Collection<int, Participant> */
    private function ordered(Board $b)
    {
        return $b->participants()
            ->orderByRaw('COALESCE(sort_key, joined_seq * '.self::GAP.') ASC')
            ->orderBy('joined_seq')
            ->get();
    }

    private function maxSortKey(Board $b, ?int $exceptId = null): int
    {
        $q = Participant::where('board_id', $b->id)->whereNotNull('sort_key');
        if ($exceptId !== null) {
            $q->where('id', '!=', $exceptId);
        }

        return (int) $q->max('sort_key');
    }

    private function lockBoard(Board $b): void
    {
        $fresh = Board::where('id', $b->id)->lockForUpdate()->first();
        if ($fresh !== null) {
            $b->setRawAttributes($fresh->getAttributes(), true);
        }
    }

    /** @return array{participant: Participant, token: string} */
    private function makeParticipant(Board $b, string $name, int $seq, string $status): array
    {
        $token = Str::random(32);
        $p = Participant::create([
            'board_id' => $b->id,
            'full_name' => $name,
            'display_label' => $this->label($b->mask_mode, $name, $seq),
            'token_hash' => $this->hash($token),
            'token_cipher' => Crypt::encryptString($token),
            'status' => $status,
            'joined_seq' => $seq,
        ]);

        return ['participant' => $p, 'token' => $token];
    }

    private function label(string $mode, string $name, int $seq): string
    {
        return match ($mode) {
            'numero' => 'N° '.$seq,
            'pseudo' => self::PSEUDOS[($seq - 1) % count(self::PSEUDOS)]
                .(intdiv($seq - 1, count(self::PSEUDOS)) > 0 ? ' '.(intdiv($seq - 1, count(self::PSEUDOS)) + 1) : ''),
            default => $this->initialLabel($name),
        };
    }

    private function initialLabel(string $name): string
    {
        $parts = explode(' ', $name);
        if (count($parts) < 2) {
            return $name;
        }

        return $parts[0].' '.mb_strtoupper(mb_substr((string) end($parts), 0, 1)).'.';
    }
}
