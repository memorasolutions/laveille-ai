<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 */

declare(strict_types=1);

namespace Modules\Core\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Bookmark extends Model
{
    public $timestamps = false;

    protected $table = 'user_bookmarks';

    protected $fillable = ['user_id', 'bookmarkable_type', 'bookmarkable_id', 'created_at'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function bookmarkable(): MorphTo
    {
        return $this->morphTo();
    }

    public static function toggle(int $userId, string $type, int $id): bool
    {
        $existing = static::where('user_id', $userId)
            ->where('bookmarkable_type', $type)
            ->where('bookmarkable_id', $id)
            ->first();

        if ($existing) {
            $existing->delete();

            return false;
        }

        static::create([
            'user_id' => $userId,
            'bookmarkable_type' => $type,
            'bookmarkable_id' => $id,
            'created_at' => now(),
        ]);

        return true;
    }
}
