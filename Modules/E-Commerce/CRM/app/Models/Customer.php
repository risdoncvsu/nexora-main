<?php

namespace Modules\Ecommerce\CRM\Models;

use Modules\Ecommerce\Models\Concerns\BelongsToClient;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use BelongsToClient, HasFactory, SoftDeletes;

    protected $table = 'crm_customers';

    protected $fillable = [
        'client_id',
        'user_id',
        'email',
        'first_name',
        'last_name',
        'phone',
        'source',
        'total_spent',
        'order_count',
        'last_purchase_at',
        'average_order_value',
        'metadata',
        'notes',

        // Customer Health & Engagement (added by 2026_08_01_000001)
        'engagement_score',
        'churn_risk',
        'last_engaged_at',

        // Compliance & Consent (added by 2026_08_01_000001)
        'opt_in_email',
        'opt_in_sms',
        'opted_in_at',

        // Other processes may set these (forge_points, tier)
        'forge_points',
        'total_forge_points_earned',
        'tier',
    ];

    protected function casts(): array
    {
        return [
            'total_spent' => 'decimal:2',
            'average_order_value' => 'decimal:2',
            'engagement_score' => 'decimal:2',
            'opt_in_email' => 'boolean',
            'opt_in_sms' => 'boolean',
            'last_purchase_at' => 'datetime',
            'opted_in_at' => 'datetime',
            'last_engaged_at' => 'datetime',
            'metadata' => 'json',
            'forge_points' => 'integer',
            'total_forge_points_earned' => 'integer',
        ];
    }

    // ─── Relationships ────────────────────────────────────────────────

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'crm_customer_tags', 'customer_id', 'tag_id');
    }

    public function segments(): BelongsToMany
    {
        return $this->belongsToMany(Segment::class, 'crm_customer_segments', 'customer_id', 'segment_id');
    }

    public function communications(): HasMany
    {
        return $this->hasMany(Communication::class, 'customer_id');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(ProductReview::class, 'user_id', 'user_id');
    }

    public function activityLog(): HasMany
    {
        return $this->hasMany(ActivityLog::class, 'customer_id');
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'customer_id');
    }

    public function openTickets(): HasMany
    {
        return $this->tickets()->whereIn('status', ['open', 'pending']);
    }

    public function ticketNotes(): HasManyThrough
    {
        return $this->hasManyThrough(TicketNote::class, Ticket::class, 'customer_id', 'ticket_id');
    }

    public function campaignLogs(): HasMany
    {
        return $this->hasMany(CampaignLog::class, 'customer_id');
    }

    public function campaignEvents(): HasManyThrough
    {
        return $this->hasManyThrough(CampaignEvent::class, CampaignLog::class, 'customer_id', 'campaign_log_id');
    }

    public function consentLogs(): HasMany
    {
        return $this->hasMany(ConsentLog::class, 'customer_id');
    }

    // ─── Accessors ────────────────────────────────────────────────────

    public function getFullNameAttribute(): string
    {
        return trim($this->first_name . ' ' . $this->last_name) ?: 'Unknown';
    }

    public function getChurnRiskLabelAttribute(): string
    {
        return match ($this->churn_risk) {
            'low'    => 'Low',
            'medium' => 'Medium',
            'high'   => 'High',
            default  => ucfirst($this->churn_risk),
        };
    }

    public function getChurnRiskColorAttribute(): string
    {
        return match ($this->churn_risk) {
            'low'    => '#22C55E',
            'medium' => '#F59E0B',
            'high'   => '#EF4444',
            default  => '#6B7280',
        };
    }

    // ─── Tier Benefits (used site-wide for pricing and shipping) ───────

    const TIER_BENEFITS = [
        'none' => [
            'item_discount'    => 0,
            'shipping_benefit' => null, // null = no shipping benefit
        ],
        'bronze' => [
            'item_discount'    => 0.05, // 5%
            'shipping_benefit' => null,
        ],
        'silver' => [
            'item_discount'    => 0.05, // 5%
            'shipping_benefit' => '50%_off', // 50% off any shipping
        ],
        'gold' => [
            'item_discount'    => 0.08, // 8%
            'shipping_benefit' => 'free_standard', // free standard shipping only
        ],
        'platinum' => [
            'item_discount'    => 0.10, // 10%
            'shipping_benefit' => 'free_general', // free any shipping method
        ],
    ];

    /**
     * Get benefits for a specific tier key.
     */
    public static function benefitsForTier(string $tier): array
    {
        return self::TIER_BENEFITS[$tier] ?? self::TIER_BENEFITS['none'];
    }

    /**
     * Load the CRM customer for a user and return their tier + benefits.
     * Returns ['tier' => string, 'benefits' => array, 'label' => string, 'color' => string, 'item_discount_pct' => int].
     */
    public static function benefitsForUser(int $userId): array
    {
        $customer = self::withoutGlobalScope('ecommerce-client')
            ->where('user_id', $userId)
            ->first(['tier']);

        $tier = $customer?->tier ?? 'none';
        $benefits = self::benefitsForTier($tier);

        return [
            'tier'              => $tier,
            'benefits'          => $benefits,
            'label'             => $customer?->getTierLabelAttribute() ?? 'No Tier',
            'color'             => $customer?->getTierColorAttribute() ?? '#6B7280',
            'item_discount_pct' => (int) (($benefits['item_discount'] ?? 0) * 100),
        ];
    }

    public function getTierLabelAttribute(): string
    {
        return match ($this->tier) {
            'bronze'   => 'Bronze',
            'silver'   => 'Silver',
            'gold'     => 'Gold',
            'platinum' => 'Platinum',
            default    => ucfirst($this->tier),
        };
    }

    public function getTierColorAttribute(): string
    {
        return match ($this->tier) {
            'bronze'   => '#CD7F32',
            'silver'   => '#A0AEC0',
            'gold'     => '#F59E0B',
            'platinum' => '#718096',
            default    => '#6B7280',
        };
    }

    // ─── Tier Calculation (shared between events and views) ────────────

    const TIER_THRESHOLDS = [
        'bronze'   => 1000,
        'silver'   => 5000,
        'gold'     => 20000,
        'platinum' => 50000,
    ];

    /**
     * Determine the highest tier a given total_spent qualifies for.
     */
    public static function resolveTier(float|int $totalSpent): string
    {
        foreach (['platinum', 'gold', 'silver', 'bronze'] as $tier) {
            if ($totalSpent >= self::TIER_THRESHOLDS[$tier]) {
                return $tier;
            }
        }
        return 'none';
    }

    /**
     * Recalculate order aggregates and tier for the user linked to a CRM customer.
     * Typically called after an order is created or its status changes.
     */
    public static function recalculateForUser(int $userId): void
    {
        $stats = \Illuminate\Support\Facades\DB::connection('ecommerce')->table('orders')
            ->where('user_id', $userId)
            ->where('status', '!=', 'cancelled')
            ->where('payment_status', '!=', 'refunded')
            ->selectRaw('coalesce(sum(total), 0) as total_spent')
            ->selectRaw('count(*) as order_count')
            ->selectRaw('max(created_at) as last_purchase_at')
            ->first();

        $totalSpent = $stats->total_spent ?? 0;
        $orderCount = $stats->order_count ?? 0;

        $tier = self::resolveTier($totalSpent);

        self::withoutGlobalScope('ecommerce-client')
            ->where('user_id', $userId)
            ->update([
                'total_spent'        => $totalSpent,
                'order_count'        => $orderCount,
                'average_order_value' => $orderCount > 0
                    ? round($totalSpent / $orderCount, 2)
                    : 0,
                'last_purchase_at'   => $stats->last_purchase_at,
                'tier'               => $tier,
            ]);
    }

    // ─── Scopes ───────────────────────────────────────────────────────

    public function scopeHighValue($query, float $threshold = 5000)
    {
        return $query->where('total_spent', '>=', $threshold);
    }

    public function scopeRecent($query, ?int $days = 30)
    {
        return $query->where('last_purchase_at', '>=', now()->subDays($days));
    }

    public function scopeByChurnRisk($query, string $risk)
    {
        return $query->where('churn_risk', $risk);
    }

    public function scopeByTier($query, string $tier)
    {
        return $query->where('tier', $tier);
    }

    public function scopeOptedIn($query, ?string $channel = null)
    {
        if ($channel === 'email') {
            return $query->where('opt_in_email', true);
        }
        if ($channel === 'sms') {
            return $query->where('opt_in_sms', true);
        }
        return $query->where(function ($q) {
            $q->where('opt_in_email', true)->orWhere('opt_in_sms', true);
        });
    }

    public function scopeEngagedBetween($query, $start, $end)
    {
        return $query->whereBetween('last_engaged_at', [$start, $end]);
    }

    public function scopeNeedsEngagement($query, ?int $days = 60)
    {
        return $query->where(function ($q) use ($days) {
            $q->where('last_engaged_at', '<', now()->subDays($days))
              ->orWhereNull('last_engaged_at');
        });
    }
}
