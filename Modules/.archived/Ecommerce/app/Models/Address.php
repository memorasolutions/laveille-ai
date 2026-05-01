<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Ecommerce\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Address extends Model
{
    use HasFactory;

    protected $table = 'ecommerce_addresses';

    protected $fillable = [
        'user_id', 'type', 'first_name', 'last_name', 'company',
        'address_line_1', 'address_line_2', 'city', 'state',
        'postal_code', 'country', 'phone', 'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected function fullName(): Attribute
    {
        return Attribute::make(
            get: fn () => "{$this->first_name} {$this->last_name}",
        );
    }

    protected function fullAddress(): Attribute
    {
        return Attribute::make(
            get: fn () => "{$this->address_line_1}, {$this->city}, {$this->state} {$this->postal_code}, {$this->country}",
        );
    }
}
