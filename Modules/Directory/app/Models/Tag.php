<?php

declare(strict_types=1);

namespace Modules\Directory\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Translatable\HasTranslations;

class Tag extends Model
{
    use HasTranslations;

    protected $table = 'directory_tags';

    public array $translatable = ['name', 'slug'];

    protected $fillable = ['name', 'slug'];

    public function tools(): BelongsToMany
    {
        return $this->belongsToMany(Tool::class, 'directory_tag_tool', 'directory_tag_id', 'directory_tool_id');
    }
}
