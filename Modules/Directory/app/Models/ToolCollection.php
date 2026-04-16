<?php

declare(strict_types=1);

namespace Modules\Directory\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class ToolCollection extends Model
{
    protected $table = 'tool_collections';

    protected $fillable = [
        'user_id',
        'name',
        'slug',
        'description',
        'is_public',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'is_public' => 'boolean',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $model): void {
            if (empty($model->slug)) {
                $slug = Str::slug($model->name);
                $originalSlug = $slug;
                $counter = 1;

                while (static::where('slug', $slug)->exists()) {
                    $slug = $originalSlug . '-' . $counter;
                    $counter++;
                }

                $model->slug = $slug;
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tools(): BelongsToMany
    {
        return $this->belongsToMany(Tool::class, 'tool_collection_items', 'collection_id', 'tool_id')
            ->withPivot('position', 'added_at')
            ->orderByPivot('position');
    }

    public function scopePublic(Builder $query): Builder
    {
        return $query->where('is_public', true);
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function addTool(int $toolId): void
    {
        if (! $this->hasTool($toolId)) {
            $maxPosition = $this->tools()->max('tool_collection_items.position') ?? -1;

            $this->tools()->attach($toolId, [
                'position' => $maxPosition + 1,
                'added_at' => Carbon::now(),
            ]);
        }
    }

    public function removeTool(int $toolId): void
    {
        $this->tools()->detach($toolId);
    }

    public function hasTool(int $toolId): bool
    {
        return $this->tools()->where('tool_collection_items.tool_id', $toolId)->exists();
    }
}
