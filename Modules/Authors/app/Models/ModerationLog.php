<?php

declare(strict_types=1);

namespace Modules\Authors\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Blog\Models\Article;

class ModerationLog extends Model
{
    use HasFactory;

    protected $table = 'article_moderation_logs';

    protected $fillable = [
        'article_id',
        'llama_guard_score',
        'gpt_oss_score',
        'local_rules_flags',
        'claude_haiku_review',
        'final_status',
        'alert_sent_at',
        'alert_recipient',
        'reviewed_by_admin_at',
        'admin_action',
    ];

    protected $casts = [
        'llama_guard_score' => 'array',
        'gpt_oss_score' => 'array',
        'local_rules_flags' => 'array',
        'alert_sent_at' => 'datetime',
        'reviewed_by_admin_at' => 'datetime',
    ];

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }
}
