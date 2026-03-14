<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\AI\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\AI\Database\Factories\CsatSurveyFactory;

class CsatSurvey extends Model
{
    use HasFactory;

    protected $fillable = [
        'ticket_id',
        'conversation_id',
        'user_id',
        'score',
        'comment',
    ];

    protected $casts = [
        'score' => 'integer',
    ];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(\Modules\AI\Models\AiConversation::class, 'conversation_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeForPeriod($query, $from, $to)
    {
        return $query->whereBetween('created_at', [$from, $to]);
    }

    public static function averageScore($from = null, $to = null): ?float
    {
        $query = static::query();
        if ($from && $to) {
            $query->forPeriod($from, $to);
        }

        return $query->avg('score');
    }

    public static function trendByDay($from, $to): array
    {
        return static::forPeriod($from, $to)
            ->selectRaw('DATE(created_at) as date, AVG(score) as avg_score, COUNT(*) as total')
            ->groupByRaw('DATE(created_at)')
            ->orderByRaw('DATE(created_at)')
            ->get()
            ->toArray();
    }

    protected static function newFactory(): CsatSurveyFactory
    {
        return CsatSurveyFactory::new();
    }
}
