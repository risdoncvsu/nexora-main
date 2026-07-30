<?php

namespace Modules\Ecommerce\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Modules\Ecommerce\Models\Concerns\BelongsToClient;
use Modules\Ecommerce\Services\ListingAvailabilityService;
use Modules\Ecommerce\Support\EcommerceClientContext;

class StorefrontListing extends Model
{
    use BelongsToClient;

    protected $fillable = ['bom_id', 'packaging_bom_id', 'sku', 'name', 'description', 'price', 'image_url', 'status'];

    protected $casts = ['price' => 'decimal:2'];

    protected $appends = ['available_quantity'];

    protected static function booted(): void
    {
        static::saving(function (self $listing): void {
            $clientId = (int) ($listing->client_id ?: app(EcommerceClientContext::class)->clientId());

            $manufacturing = DB::connection('manufacturing');
            $bom = $manufacturing->table('product_boms')
                ->where('id', $listing->bom_id)
                ->where('client_id', $clientId)
                ->where('status', 'active');

            if ($manufacturing->getSchemaBuilder()->hasColumn('product_boms', 'bom_type')) {
                $bom->where(function ($query): void {
                    $query->whereNull('bom_type')->orWhere('bom_type', 'prebuilt');
                });
            }

            if (! $clientId || ! $bom->exists()) {
                throw new \LogicException('Select an active Bill of Materials owned by this client.');
            }

            if (! $listing->packaging_bom_id) {
                return;
            }

            $packagingBom = $manufacturing->table('product_boms')
                ->where('id', $listing->packaging_bom_id)
                ->where('client_id', $clientId)
                ->where('status', 'active');

            if (! $manufacturing->getSchemaBuilder()->hasColumn('product_boms', 'bom_type')) {
                throw new \LogicException('Packaging BOMs require the Manufacturing BOM type migration.');
            }

            $packagingBom->where('bom_type', 'packaging');

            if (! $packagingBom->exists()) {
                throw new \LogicException('Select an active packaging Bill of Materials owned by this client.');
            }
        });
    }

    public function getAvailableQuantityAttribute(): int
    {
        $clientId = (int) ($this->client_id ?: app(EcommerceClientContext::class)->clientId());

        return $clientId
            ? app(ListingAvailabilityService::class)->availableUnits($clientId, (int) $this->bom_id)
            : 0;
    }
}
