<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Newsletter\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Newsletter\States\CampaignState;
use Modules\Newsletter\States\DraftCampaignState;
use Modules\Newsletter\States\SentCampaignState;
use Modules\Tenancy\Traits\BelongsToTenant;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\ModelStates\HasStates;

class Campaign extends Model
{
    use BelongsToTenant, HasFactory, HasStates, LogsActivity;

    protected $table = 'newsletter_campaigns';

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $eventName): string => "Campagne {$eventName}");
    }

    protected $fillable = [
        'subject',
        'content',
        'status',
        'sent_at',
        'recipient_count',
        'tenant_id',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'status' => CampaignState::class,
        'recipient_count' => 'integer',
    ];

    public function scopeDraft($query)
    {
        return $query->whereState('status', DraftCampaignState::class);
    }

    public function scopeSent($query)
    {
        return $query->whereState('status', SentCampaignState::class);
    }

    public function isDraft(): bool
    {
        return $this->status->equals(DraftCampaignState::class);
    }

    public function isSent(): bool
    {
        return $this->status->equals(SentCampaignState::class);
    }

    protected static function newFactory(): \Modules\Newsletter\Database\Factories\CampaignFactory
    {
        return \Modules\Newsletter\Database\Factories\CampaignFactory::new();
    }
}
