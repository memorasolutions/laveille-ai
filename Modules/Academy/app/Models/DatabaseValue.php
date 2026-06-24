<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * F20 - BASE DE DONNÉES collaborative : une VALEUR (un champ d'une entrée). value est du
 * texte BRUT borné ; le rendu (strip markdown pour text/textarea, échappement + lien sûr
 * pour url, échappement pour number/select) est fait à l'affichage par DatabaseService
 * (anti-XSS). Aucune logique métier ici : simple porteur de donnée.
 */

declare(strict_types=1);

namespace Modules\Academy\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int         $id
 * @property int         $database_entry_id
 * @property int         $database_field_id
 * @property string|null $value
 */
class DatabaseValue extends Model
{
    protected $table = 'academy_database_values';

    protected $fillable = [
        'database_entry_id',
        'database_field_id',
        'value',
    ];

    protected $casts = [
        'database_entry_id' => 'integer',
        'database_field_id' => 'integer',
    ];

    public function entry(): BelongsTo
    {
        return $this->belongsTo(DatabaseEntry::class, 'database_entry_id');
    }

    public function field(): BelongsTo
    {
        return $this->belongsTo(DatabaseField::class, 'database_field_id');
    }
}
