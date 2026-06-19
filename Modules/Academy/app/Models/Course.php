<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Academy\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Traits\LogsActivityStandard;

/**
 * @property int         $id
 * @property string      $slug
 * @property string      $title
 * @property string|null $subtitle
 * @property string|null $summary
 * @property string|null $description
 * @property string      $language
 * @property string      $level
 * @property int|null    $duration_minutes
 * @property int|null    $image_media_id
 * @property string      $visibility
 * @property string      $access_type
 * @property int|null    $price_cents
 * @property string      $currency
 * @property string|null $stripe_price_id
 * @property string      $status
 * @property \Illuminate\Support\Carbon|null $published_at
 * @property array|null  $seo_jsonld
 * @property array|null  $faq_dictionary_ids
 * @property int|null    $tools_collection_id
 * @property int|null    $created_by
 * @property int|null    $updated_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
class Course extends Model
{
    use SoftDeletes;
    use LogsActivityStandard;

    protected array $activitylogFields = ['title', 'status', 'visibility', 'access_type'];

    protected string $activitylogName = 'academy.course';

    protected $fillable = [
        'slug',
        'title',
        'subtitle',
        'summary',
        'description',
        'language',
        'level',
        'duration_minutes',
        'image_media_id',
        'visibility',
        'access_type',
        'price_cents',
        'currency',
        'stripe_price_id',
        'status',
        'published_at',
        'seo_jsonld',
        'faq_dictionary_ids',
        'tools_collection_id',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'published_at'       => 'datetime',
        'seo_jsonld'         => 'array',
        'faq_dictionary_ids' => 'array',
        'duration_minutes'   => 'integer',
        'price_cents'        => 'integer',
    ];

    // Relations

    public function chapters(): HasMany
    {
        return $this->hasMany(Chapter::class);
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    public function courseRoles(): HasMany
    {
        return $this->hasMany(CourseRole::class);
    }

    public function certificatesIssued(): HasMany
    {
        return $this->hasMany(CertificateIssued::class);
    }

    public function progresses(): HasMany
    {
        return $this->hasMany(Progress::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // Scopes

    public function scopePublished($query)
    {
        return $query->where('status', 'published')->where('visibility', 'public');
    }
}
