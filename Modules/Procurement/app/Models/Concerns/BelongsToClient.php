<?php

namespace Modules\Procurement\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Modules\Procurement\Support\SchemaProbe;

trait BelongsToClient
{
    public function getConnectionName(): ?string
    {
        return 'procurement';
    }

    protected static function bootBelongsToClient(): void
    {
        static::addGlobalScope('client', function (Builder $query): void {
            $table = $query->getModel()->getTable();

            if (! SchemaProbe::hasColumn('procurement', $table, 'client_id')) {
                // Never leak legacy standalone Procurement records while the
                // database is waiting for its explicit client-key upgrade.
                $query->whereRaw('1 = 0');

                return;
            }

            $query->where($table.'.client_id', (int) session('employee_client_id'));
        });

        static::creating(function ($model): void {
            if (empty($model->client_id)) {
                $model->client_id = (int) session('employee_client_id');
            }
        });
    }

    /**
     * Escape hatch for the few lookups that are deliberately global: po_number
     * and shipment_number carry a database-wide UNIQUE constraint, so the
     * "what is the next free number" probe must see every tenant's rows or it
     * will hand out a number that already exists.
     */
    public function scopeAcrossClients(Builder $query): Builder
    {
        return $query->withoutGlobalScope('client');
    }
}
