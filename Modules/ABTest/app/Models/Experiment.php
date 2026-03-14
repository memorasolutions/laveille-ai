<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\ABTest\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Experiment extends Model
{
    use HasFactory;

    protected $table = 'ab_experiments';

    protected static function newFactory(): \Modules\ABTest\Database\Factories\ExperimentFactory
    {
        return \Modules\ABTest\Database\Factories\ExperimentFactory::new();
    }

    protected $fillable = [
        'name',
        'slug',
        'description',
        'variants',
        'status',
        'winner',
        'started_at',
        'ended_at',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'variants' => 'array',
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
        ];
    }

    /** @return HasMany<ABParticipation, $this> */
    public function participations(): HasMany
    {
        return $this->hasMany(ABParticipation::class);
    }

    /** @param Builder<Experiment> $query */
    public function scopeRunning(Builder $query): Builder
    {
        return $query->where('status', 'running');
    }

    /** @param Builder<Experiment> $query */
    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', 'completed');
    }

    public function isRunning(): bool
    {
        return $this->status === 'running';
    }

    public function start(): void
    {
        $this->update([
            'status' => 'running',
            'started_at' => now(),
        ]);
    }

    public function complete(string $winner): void
    {
        $this->update([
            'status' => 'completed',
            'winner' => $winner,
            'ended_at' => now(),
        ]);
    }

    /** @return array<string, array{participants: int, conversions: int, rate: float}> */
    public function getResults(): array
    {
        $results = [];

        foreach ($this->variants as $variant) {
            $participants = $this->participations()->where('variant', $variant)->count();
            $conversions = $this->participations()
                ->where('variant', $variant)
                ->whereNotNull('converted_at')
                ->count();

            $results[$variant] = [
                'participants' => $participants,
                'conversions' => $conversions,
                'rate' => $participants > 0 ? round($conversions / $participants, 4) : 0.0,
            ];
        }

        return $results;
    }
}
