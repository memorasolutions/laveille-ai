<?php

declare(strict_types=1);

namespace Modules\Blog\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Blog\Database\Factories\ArticleRevisionFactory;

class ArticleRevision extends Model
{
    use HasFactory;

    protected static function newFactory(): ArticleRevisionFactory
    {
        return ArticleRevisionFactory::new();
    }

    protected $fillable = [
        'article_id',
        'user_id',
        'title',
        'content',
        'excerpt',
        'status',
        'meta',
        'revision_number',
        'summary',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'meta' => 'array',
        ];
    }

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
