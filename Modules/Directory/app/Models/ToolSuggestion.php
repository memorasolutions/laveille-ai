<?php

declare(strict_types=1);

namespace Modules\Directory\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ToolSuggestion extends Model
{
    protected $table = 'directory_suggestions';

    protected $fillable = [
        'user_id', 'directory_tool_id', 'suggestable_type', 'suggestable_id',
        'field', 'current_value', 'suggested_value', 'reason',
        'status', 'admin_note', 'votes_count',
    ];

    protected $casts = [
        'votes_count' => 'integer',
    ];

    // Polymorphic relation (new)
    public function suggestable(): MorphTo
    {
        return $this->morphTo();
    }

    // Legacy relation (backward compat)
    public function tool(): BelongsTo
    {
        return $this->belongsTo(Tool::class, 'directory_tool_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Get the display name of the source (for roadmap badges).
     */
    public function getSourceLabel(): array
    {
        return match ($this->suggestable_type) {
            'Modules\\Directory\\Models\\Tool' => ['name' => 'Répertoire', 'emoji' => '🔍', 'color' => '#0B7285'],
            'Modules\\Dictionary\\Models\\Term' => ['name' => 'Glossaire', 'emoji' => '📚', 'color' => '#8E44AD'],
            'Modules\\Acronyms\\Models\\Acronym' => ['name' => 'Acronymes', 'emoji' => '🎓', 'color' => '#059669'],
            default => ['name' => 'Général', 'emoji' => '💡', 'color' => '#6B7280'],
        };
    }

    /**
     * Get the suggestable model's name for display.
     */
    public function getItemName(): string
    {
        // Try polymorphic first, fallback to legacy tool relation
        if ($this->suggestable) {
            return $this->suggestable->name ?? $this->suggestable->acronym ?? '';
        }

        return $this->tool->name ?? '';
    }

    public static function fieldLabels(): array
    {
        return [
            'definition' => 'Définition',
            'analogy' => 'Analogie',
            'example' => 'Exemple',
            'full_name' => 'Nom complet',
            'website_url' => 'Site web',
            'description' => 'Description',
            'short_description' => 'Description courte',
            'pricing' => 'Tarification',
            'url' => 'URL du site',
            'core_features' => 'Fonctionnalités',
            'how_to_use' => 'Comment utiliser',
            'use_cases' => "Cas d'usage",
            'other' => 'Autre',
        ];
    }
}
