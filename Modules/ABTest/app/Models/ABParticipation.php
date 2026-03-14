<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\ABTest\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\ABTest\Database\Factories\ABParticipationFactory;

class ABParticipation extends Model
{
    use HasFactory;

    protected $table = 'ab_participations';

    protected $fillable = [
        'experiment_id',
        'user_id',
        'session_id',
        'variant',
        'converted_at',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'converted_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Experiment, $this> */
    public function experiment(): BelongsTo
    {
        return $this->belongsTo(Experiment::class);
    }

    protected static function newFactory(): ABParticipationFactory
    {
        return ABParticipationFactory::new();
    }
}
