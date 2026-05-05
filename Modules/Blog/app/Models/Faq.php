<?php

declare(strict_types=1);

namespace Modules\Blog\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Traits\HasPublishedState;
use Spatie\Translatable\HasTranslations;

class Faq extends Model
{
    use HasPublishedState;
    use HasTranslations;
    use SoftDeletes;

    protected $table = 'blog_faqs';

    public array $translatable = ['question', 'answer'];

    protected $fillable = [
        'article_id',
        'question',
        'answer',
        'position',
        'is_published',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'position' => 'integer',
    ];

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }

    // 2026-05-05 #146 : scopePublished mutualise via HasPublishedState (DRY Core).

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('position');
    }
}
