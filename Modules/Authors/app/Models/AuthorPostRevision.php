<?php

declare(strict_types=1);

namespace Modules\Authors\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuthorPostRevision extends Model
{
    use HasFactory;

    protected $table = 'author_post_revisions';

    protected $fillable = [
        'author_post_id',
        'user_id',
        'body_markdown_snapshot',
        'change_summary',
    ];

    public function post(): BelongsTo
    {
        return $this->belongsTo(AuthorPost::class, 'author_post_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
