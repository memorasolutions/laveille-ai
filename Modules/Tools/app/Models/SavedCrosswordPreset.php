<?php

declare(strict_types=1);

namespace Modules\Tools\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class SavedCrosswordPreset extends Model
{
    use SoftDeletes;

    /**
     * Slugs réservés interdits pour custom_slug.
     * Inclut : segments routes existants + actions futures + collisions /jeumc/.
     */
    public const RESERVED_SLUGS = [
        'index', 'admin', 'api', 'nouveau', 'creer', 'create', 'mes-grilles',
        'populaires', 'recents', 'themes', 'share', 'qr', 'edit', 'json',
        'csv-template', 'csv-import', 'csv-export', 'generate',
        'pdf-blank', 'pdf-solution', 'embed', 'preview', 'login', 'logout',
    ];

    protected $table = 'saved_crossword_presets';

    protected $fillable = ['user_id', 'name', 'config_text', 'params', 'is_public', 'play_count', 'custom_slug', 'qr_options', 'fingerprint'];

    protected $casts = [
        'params' => 'array',
        'qr_options' => 'array',
        'is_public' => 'boolean',
        'play_count' => 'integer',
    ];

    /**
     * Mutator : normalise custom_slug (lowercase, ASCII, alphanum + hyphen, collapse `--`).
     */
    protected function customSlug(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value) => $value
                ? trim(preg_replace('/-{2,}/', '-', preg_replace('/[^a-z0-9-]/', '', strtolower(Str::ascii(str_replace(' ', '-', $value))))), '-') ?: null
                : null,
        );
    }

    protected static function booted(): void
    {
        static::creating(function (self $preset) {
            if (empty($preset->public_id)) {
                do {
                    $id = Str::random(12);
                } while (static::where('public_id', $id)->exists());
                $preset->public_id = $id;
            }
        });

        // 2026-05-05 #101 : auto-set fingerprint à chaque sauvegarde (création + update si config_text changé).
        static::saving(function (self $preset) {
            if ($preset->isDirty('config_text') || empty($preset->fingerprint)) {
                $preset->fingerprint = static::computeFingerprint((string) $preset->config_text);
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    /**
     * 2026-05-05 #97 : retourne l'identifiant à utiliser dans /jeumc/{...} (slug si défini, sinon public_id).
     */
    public function getShareSlugAttribute(): string
    {
        return $this->custom_slug ?: $this->public_id;
    }

    /**
     * 2026-05-05 #97 : URL publique préférée (slug custom si défini).
     */
    public function getShareUrlAttribute(): string
    {
        return url('/jeumc/'.$this->share_slug);
    }

    /**
     * 2026-05-05 #97 : trouve un preset par slug custom OU public_id (anti-collision : custom_slug d'abord).
     */
    public static function findByShareIdentifier(string $identifier): ?self
    {
        return static::where('custom_slug', $identifier)
            ->orWhere('public_id', $identifier)
            ->first();
    }

    /**
     * 2026-05-05 #101 : calcule fingerprint SHA256 des paires triées normalisées.
     * Détecte grilles équivalentes (mêmes paires clue→answer) indépendamment de l'ordre / casse / accents.
     */
    public static function computeFingerprint(string $configText): string
    {
        $configText = trim($configText);
        $rawPairs = [];

        // Format JSON moderne (S80+) : {"pairs":[{"clue":"x","answer":"y"}]}
        if (str_starts_with($configText, '{')) {
            $data = @json_decode($configText, true);
            if (is_array($data) && isset($data['pairs']) && is_array($data['pairs'])) {
                foreach ($data['pairs'] as $p) {
                    if (is_array($p) && isset($p['clue'], $p['answer'])) {
                        $rawPairs[] = [(string) $p['clue'], (string) $p['answer']];
                    }
                }
            }
        }

        // Format legacy 1 ligne par paire "indice / mot"
        if (empty($rawPairs)) {
            $lines = preg_split('/\r\n|\n|\r/', $configText) ?: [];
            foreach ($lines as $line) {
                $line = trim($line);
                if (! str_contains($line, ' / ')) {
                    continue;
                }
                [$clue, $answer] = array_map('trim', explode(' / ', $line, 2));
                if ($clue !== '' && $answer !== '') {
                    $rawPairs[] = [$clue, $answer];
                }
            }
        }

        $normPairs = [];
        foreach ($rawPairs as [$clue, $answer]) {
            $clueNorm = mb_strtolower(Str::ascii($clue));
            $answerNorm = mb_strtoupper(Str::ascii($answer));
            $clueNorm = preg_replace('/\s+/', ' ', trim($clueNorm));
            $answerNorm = preg_replace('/[^A-Z]/', '', $answerNorm);
            if ($clueNorm !== '' && $answerNorm !== '') {
                $normPairs[] = $clueNorm.'|'.$answerNorm;
            }
        }
        sort($normPairs);

        return hash('sha256', implode("\n", $normPairs));
    }

    /**
     * 2026-05-05 #101 : trouve un duplicate public chez un autre user (mêmes paires).
     * Ignore le preset courant et retourne le premier match public.
     */
    public function findPublicDuplicate(): ?self
    {
        if (! $this->fingerprint) {
            return null;
        }

        return static::where('fingerprint', $this->fingerprint)
            ->where('is_public', true)
            ->where('id', '!=', $this->id)
            ->first();
    }

    /**
     * 2026-05-05 #94 : difficulté lue depuis params JSON (défaut "Moyen").
     */
    public function getDifficultyAttribute(): string
    {
        return (string) ($this->params['difficulty'] ?? 'Moyen');
    }

    /**
     * 2026-05-05 #94 : thème optionnel lu depuis params (peut être vide).
     */
    public function getThemeAttribute(): string
    {
        return (string) ($this->params['theme'] ?? '');
    }

    /**
     * 2026-05-05 #94 : nombre de paires extrait du config_text JSON ou format legacy.
     */
    public function getWordCountAttribute(): int
    {
        $cfg = $this->config_text ?? '';
        // Format JSON moderne (S80+)
        if (str_starts_with(trim($cfg), '{')) {
            $data = @json_decode($cfg, true);
            if (is_array($data) && isset($data['pairs']) && is_array($data['pairs'])) {
                return count($data['pairs']);
            }
        }
        // Format legacy (1 ligne par paire "indice / mot")
        $lines = array_filter(array_map('trim', preg_split("/\r\n|\n|\r/", $cfg) ?: []));

        return count(array_filter($lines, fn ($l) => str_contains($l, ' / ')));
    }
}
