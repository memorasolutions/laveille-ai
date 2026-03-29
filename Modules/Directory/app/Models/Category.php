<?php

declare(strict_types=1);

namespace Modules\Directory\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Translatable\HasTranslations;

class Category extends Model
{
    use HasTranslations;

    protected $table = 'directory_categories';

    public array $translatable = ['name', 'slug', 'description'];

    protected $fillable = ['name', 'slug', 'description', 'icon', 'sort_order'];

    public function tools(): BelongsToMany
    {
        return $this->belongsToMany(Tool::class, 'directory_category_tool', 'directory_category_id', 'directory_tool_id');
    }
}
