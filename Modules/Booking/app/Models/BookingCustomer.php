<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

namespace Modules\Booking\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Booking\Database\Factories\BookingCustomerFactory;

class BookingCustomer extends Model
{
    use HasFactory;

    protected static function newFactory(): BookingCustomerFactory
    {
        return BookingCustomerFactory::new();
    }

    protected $table = 'booking_customers';

    protected $fillable = [
        'first_name', 'last_name', 'email', 'phone', 'notes',
        'timezone', 'no_show_count', 'portal_token',
        'total_bookings', 'total_spent', 'last_booking_at',
    ];

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class, 'customer_id');
    }

    protected function fullName(): Attribute
    {
        return Attribute::get(fn () => trim($this->first_name.' '.$this->last_name));
    }
}
