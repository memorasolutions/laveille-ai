<?php

declare(strict_types=1);

namespace Modules\Directory\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ToolResource extends Model
{
    use \Modules\Core\Traits\HasModerationStatus;
    use \Modules\Voting\Traits\HasCommunityVotes;

    protected $table = 'directory_resources';

    protected $fillable = [
        'directory_tool_id', 'user_id', 'url', 'title',
        'type', 'language', 'level', 'thumbnail', 'video_id',
        'video_summary', 'duration_seconds', 'channel_name',
        'channel_url', 'is_approved',
    ];

    public static function detectLevel(string $title): string
    {
        $t = mb_strtolower($title);

        $advanced = ['avancé', 'advanced', 'pro tips', 'expert', 'master', 'deep dive', 'optimisation', 'architecture', 'scaling', 'enterprise', 'astuces pro'];
        foreach ($advanced as $kw) {
            if (str_contains($t, $kw)) {
                return 'advanced';
            }
        }

        $beginner = ['débutant', 'beginner', 'getting started', 'premiers pas', 'introduction', 'basics', 'fundamentals', '101', 'pour commencer', 'débuter', 'apprendre', 'learn', 'easy', 'simple', 'guide complet', 'from scratch', 'de zéro'];
        foreach ($beginner as $kw) {
            if (str_contains($t, $kw)) {
                return 'beginner';
            }
        }

        return 'intermediate';
    }

    protected $casts = [
        'is_approved' => 'boolean',
    ];

    public function tool(): BelongsTo
    {
        return $this->belongsTo(Tool::class, 'directory_tool_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('is_approved', true);
    }

    /**
     * Le champ thumbnail pointe-t-il vers un fichier LOCAL (capture téléversée par un
     * modérateur via ScreenshotUploadService, ex. ModerationController::uploadResourceScreenshot())
     * plutôt que vers une URL distante (YouTube, oEmbed...) ?
     *
     * ScreenshotUploadService::upload() stocke toujours un chemin préfixé d'un "/"
     * (prefixSlash=true par défaut), jamais une URL "http...". Format vérifié en base le
     * 2026-08-22 : les captures locales existantes sont toutes "/directory-resources-
     * screenshots/{id}.jpg". Même convention déjà utilisée pour Tool::screenshot
     * (voir ToolObserver et PublicToolsController).
     *
     * @author MEMORA solutions <info@memora.ca>
     */
    public function hasLocalThumbnail(): bool
    {
        return ! empty($this->thumbnail) && ! str_starts_with($this->thumbnail, 'http');
    }

    /**
     * URL de la miniature à afficher, dans l'ordre de préférence :
     * 1. capture LOCALE téléversée à la main (thumbnail = chemin relatif) ;
     * 2. sinon, miniature YouTube reconstruite depuis video_id (comportement historique,
     *    inchangé, pour toute ressource vidéo sans capture locale) ;
     * 3. sinon, aucune miniature.
     *
     * BUG corrigé le 2026-08-22 : Modules/Directory/resources/views/public/show.blade.php
     * reconstruisait TOUJOURS l'URL YouTube dès que video_id était présent, en ignorant
     * thumbnail sans condition. Une capture téléversée par un administrateur sur une
     * ressource vidéo (thumbnail local + video_id tous deux non vides) n'était donc JAMAIS
     * affichée : le chemin local restait mort en silence.
     *
     * @author MEMORA solutions <info@memora.ca>
     */
    public function displayThumbnailUrl(): ?string
    {
        if ($this->hasLocalThumbnail()) {
            return $this->thumbnail . '?v=' . ($this->updated_at?->timestamp ?? time());
        }

        if (! empty($this->video_id)) {
            // hqdefault = la SEULE miniature YouTube garantie réelle pour toutes les vidéos
            // (maxresdefault renvoie un placeholder gris 120x90 « 200 OK » pour les vidéos
            // non-HD → onerror inopérant).
            return "https://img.youtube.com/vi/{$this->video_id}/hqdefault.jpg";
        }

        return null;
    }
}
