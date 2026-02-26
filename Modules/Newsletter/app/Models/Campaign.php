<?php

declare(strict_types=1);

namespace Modules\Newsletter\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Newsletter\States\CampaignState;
use Modules\Newsletter\States\DraftCampaignState;
use Modules\Newsletter\States\SentCampaignState;
use Spatie\ModelStates\HasStates;

class Campaign extends Model
{
    use HasFactory, HasStates;

    protected $table = 'newsletter_campaigns';

    protected $fillable = [
        'subject',
        'content',
        'status',
        'sent_at',
        'recipient_count',
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
