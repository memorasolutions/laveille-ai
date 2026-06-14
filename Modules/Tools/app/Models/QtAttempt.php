<?php

declare(strict_types=1);

namespace Modules\Tools\Models;

use Illuminate\Database\Eloquent\Model;

class QtAttempt extends Model
{
    protected $table = 'qt_attempts';

    public $timestamps = false;

    protected $fillable = [
        'qt',
        'mode',
        'defi_number',
        'correct_count',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];
}
