<?php

declare(strict_types=1);

namespace Modules\Tools\Models;

use Illuminate\Database\Eloquent\Model;

class QuestProgress extends Model
{
    protected $table = 'quest_progress';

    protected $fillable = [
        'user_email',
        'current_chapter',
        'completed_chapters',
        'choices',
        'badges',
        'streak_days',
        'last_active_date',
    ];

    protected $casts = [
        'completed_chapters' => 'array',
        'choices' => 'array',
        'badges' => 'array',
        'last_active_date' => 'date',
    ];
}
