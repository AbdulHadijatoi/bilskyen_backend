<?php

namespace App\Models;

use App\Services\Marketing\TrafficAttributionService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lead extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'vehicle_id',
        'buyer_user_id',
        'dealer_id',
        'assigned_user_id',
        'lead_stage_id',
        'lead_intent_id',
        'source_id',
        'lead_category_id',
        'lost_reason_id',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'referrer_url',
        'traffic_source',
        'last_activity_at',
        'first_contacted_at',
        'created_at',
    ];

    protected $appends = [
        'effective_traffic_source',
    ];

    protected $casts = [
        'last_activity_at' => 'datetime',
        'first_contacted_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function getEffectiveTrafficSourceAttribute(): string
    {
        if (is_string($this->traffic_source) && $this->traffic_source !== '') {
            return $this->traffic_source;
        }

        return app(TrafficAttributionService::class)->classify(
            $this->utm_source,
            null,
            $this->referrer_url
        );
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<static>  $query
     * @return \Illuminate\Database\Eloquent\Builder<static>
     */
    public function scopeEffectiveTrafficSource($query, string $source)
    {
        $metaQuery = function ($q): void {
            $q->where('traffic_source', TrafficAttributionService::SOURCE_META)
                ->orWhere(function ($legacy): void {
                    $legacy->whereNull('traffic_source')
                        ->where(function ($utm): void {
                            foreach (['facebook', 'fb', 'ig', 'instagram', 'meta', 'an', 'fbads', 'facebookads'] as $src) {
                                $utm->orWhereRaw('LOWER(utm_source) = ?', [$src]);
                            }
                            $utm->orWhere('referrer_url', 'like', '%facebook.%')
                                ->orWhere('referrer_url', 'like', '%instagram.%')
                                ->orWhere('referrer_url', 'like', '%fb.%')
                                ->orWhere('referrer_url', 'like', '%l.facebook.com%')
                                ->orWhere('referrer_url', 'like', '%lm.facebook.com%')
                                ->orWhere('referrer_url', 'like', '%m.facebook.com%');
                        });
                });
        };

        if ($source === TrafficAttributionService::SOURCE_META) {
            return $query->where($metaQuery);
        }

        return $query->whereNot($metaQuery);
    }

    /**
     * Get vehicle for this lead
     */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    /**
     * Get buyer user for this lead
     */
    public function buyerUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_user_id');
    }

    /**
     * Get dealer for this lead
     */
    public function dealer(): BelongsTo
    {
        return $this->belongsTo(Dealer::class);
    }

    /**
     * Get assigned user for this lead
     */
    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    /**
     * Get lead stage for this lead
     */
    public function leadStage(): BelongsTo
    {
        return $this->belongsTo(LeadStage::class);
    }

    /**
     * Get lead intent for this lead
     */
    public function leadIntent(): BelongsTo
    {
        return $this->belongsTo(LeadIntent::class);
    }

    /**
     * Get source for this lead
     */
    public function source(): BelongsTo
    {
        return $this->belongsTo(Source::class);
    }

    /**
     * Get lead category for this lead
     */
    public function leadCategory(): BelongsTo
    {
        return $this->belongsTo(LeadCategory::class);
    }

    /**
     * Get stage history for this lead
     */
    public function stageHistory(): HasMany
    {
        return $this->hasMany(LeadStageHistory::class);
    }

    /**
     * Get chat threads for this lead
     */
    /**
     * Get chat threads for this lead
     */
    public function chatThreads(): HasMany
    {
        return $this->hasMany(ChatThread::class);
    }

    /**
     * Get the initial enquiry for this lead
     */
    public function enquiry(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Enquiry::class);
    }

    public function notes(): HasMany
    {
        return $this->hasMany(LeadNote::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(LeadTask::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(LeadActivity::class);
    }

    public function reminders(): HasMany
    {
        return $this->hasMany(LeadReminder::class);
    }

    public function lostReason(): BelongsTo
    {
        return $this->belongsTo(LeadLostReason::class, 'lost_reason_id');
    }

    /**
     * Human-readable buyer label for list views (dashboard, feeds).
     * Uses linked user, enquiry contact fields, then lead category as fallback.
     */
    public function resolveBuyerDisplayName(): ?string
    {
        $buyerName = trim((string) ($this->buyerUser?->name ?? ''));
        if ($buyerName !== '' && ! $this->isPlaceholderBuyerLabel($buyerName)) {
            return $buyerName;
        }

        $enquiry = $this->enquiry;
        if ($enquiry) {
            $enquiryName = trim((string) ($enquiry->name ?? ''));
            if ($enquiryName !== '' && ! $this->isPlaceholderBuyerLabel($enquiryName)) {
                return $enquiryName;
            }

            $phone = trim((string) ($enquiry->phone ?? ''));
            if ($phone !== '') {
                return $phone;
            }

            $email = trim((string) ($enquiry->email ?? ''));
            if ($email !== '') {
                return $email;
            }
        }

        $categoryName = trim((string) ($this->leadCategory?->name ?? ''));
        if ($categoryName !== '') {
            return $categoryName;
        }

        return null;
    }

    private function isPlaceholderBuyerLabel(string $value): bool
    {
        return in_array(strtolower($value), ['guest', 'n/a', 'na', 'unknown', 'ukendt'], true);
    }
}
