<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Newsletter\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class NewsletterPromptPreset extends Model
{
    use SoftDeletes;
    protected $table = 'newsletter_prompt_presets';

    protected $fillable = [
        'name',
        'blocks',
        'is_default',
        'version',
    ];

    protected $casts = [
        'blocks'     => 'array',
        'is_default' => 'boolean',
        'version'    => 'integer',
    ];

    /**
     * Retourne le preset par défaut, ou le plus récent si aucun n'est marqué.
     *
     * @return static|null
     */
    public static function loadDefault(): ?self
    {
        /** @var static|null $result */
        $result = static::where('is_default', true)->first()
            ?? static::latest()->first();

        return $result;
    }

    /**
     * Marque ce preset comme défaut et retire la marque des autres.
     * Enveloppé dans une transaction pour garantir la cohérence (un seul défaut à la fois).
     */
    public function setAsDefault(): void
    {
        DB::transaction(function (): void {
            static::where('id', '!=', $this->id)->update(['is_default' => false]);
            $this->update(['is_default' => true]);
        });
    }
}
