<?php

// Author: MEMORA solutions, https://memora.solutions ; info@memora.ca

declare(strict_types=1);

namespace Modules\Catalog\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Modules\Tenancy\Traits\BelongsToTenant;

/**
 * Demande de cours personnalisé soumise depuis la vitrine publique /cours.
 *
 * @property int         $id
 * @property string      $nom
 * @property string      $courriel
 * @property string|null $organisation
 * @property string      $sujet
 * @property int|null    $heures
 * @property string|null $niveau
 * @property int|null    $participants
 * @property string      $message
 * @property string      $status        'new' (boîte) ou 'spam' (quarantaine)
 * @property string|null $spam_reason
 * @property string|null $ip_address
 * @property int|null    $tenant_id
 */
class CourseRequest extends Model
{
    use BelongsToTenant;

    protected $table = 'course_requests';

    protected $fillable = [
        'nom',
        'courriel',
        'organisation',
        'sujet',
        'heures',
        'niveau',
        'participants',
        'message',
        'status',
        'spam_reason',
        'ip_address',
        'tenant_id',
    ];

    /** Demandes légitimes (non quarantaine). */
    public function scopeNew(Builder $query): void
    {
        $query->where('status', 'new');
    }

    /** Demandes mises en quarantaine (spam probable). */
    public function scopeSpam(Builder $query): void
    {
        $query->where('status', 'spam');
    }
}
