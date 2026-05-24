<?php

declare(strict_types=1);

namespace Modules\Authors\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImageVariant extends Model
{
    use HasFactory;

    protected $table = 'author_image_variants';

    protected $fillable = [
        'author_profile_id',
        'original_path',
        'variant_path',
        'format',
        'size_width',
        'size_height',
        'file_size_bytes',
        'is_open_graph',
        'is_twitter_card',
        'alt_text',
    ];

    protected $casts = [
        'is_open_graph' => 'boolean',
        'is_twitter_card' => 'boolean',
        'size_width' => 'integer',
        'size_height' => 'integer',
        'file_size_bytes' => 'integer',
    ];

    public function authorProfile(): BelongsTo
    {
        return $this->belongsTo(AuthorProfile::class);
    }
}
