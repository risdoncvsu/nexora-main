<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillPackingMaterials extends Command
{
    protected $signature = 'inventory:backfill-packing-materials {clientId : Client ID to synchronize}';

    protected $description = 'Copy already-received packaging supplies into Order Fulfillment packing materials';

    /** @return array{name:string,is_box:bool,box_size:?string}|null */
    private function materialMeta(string $name): ?array
    {
        $normalized = strtolower(trim($name));
        $isBox = str_contains($normalized, 'box') || str_contains($normalized, 'carton');

        if (! $isBox && ! preg_match('/bubble\s*wrap|packing\s*tape|foam\s*insert|(?:silica\s*)?gels?|fragile\s*tape/', $normalized)) {
            return null;
        }

        $boxSize = $isBox
            ? (str_contains($normalized, 'small') ? 'Small' : (str_contains($normalized, 'medium') ? 'Medium' : (str_contains($normalized, 'large') ? 'Large' : 'Standard')))
            : null;

        return [
            'name' => match (true) {
                $isBox => $name,
                (bool) preg_match('/bubble\s*wrap/', $normalized) => 'Bubble Wrap',
                (bool) preg_match('/packing\s*tape/', $normalized) => 'Packing Tape',
                (bool) preg_match('/foam\s*insert/', $normalized) => 'Foam Inserts',
                (bool) preg_match('/(?:silica\s*)?gels?/', $normalized) => 'Silica Gel Packs',
                default => 'Fragile Tape',
            },
            'is_box' => $isBox,
            'box_size' => $boxSize,
        ];
    }

    public function handle(): int
    {
        $clientId = (int) $this->argument('clientId');
        $inventory = DB::connection('inventory');

        $supplies = $inventory->table('items as items')
            ->leftJoin('stock_levels as levels', 'levels.item_id', '=', 'items.id')
            ->where('items.client_id', $clientId)
            ->select('items.name', DB::raw('COALESCE(SUM(levels.stock), 0) as stock_qty'))
            ->groupBy('items.id', 'items.name')
            ->get();

        $updated = 0;
        foreach ($supplies as $supply) {
            $name = (string) $supply->name;
            $meta = $this->materialMeta($name);
            if (! $meta) {
                continue;
            }

            $row = $inventory->table('packing_materials')
                ->where('client_id', $clientId)
                ->whereRaw('LOWER(name) = LOWER(?)', [$meta['name']])
                ->first();

            $values = [
                'stock_qty' => (int) $supply->stock_qty,
                'is_box' => $meta['is_box'],
                'box_size' => $meta['box_size'],
                'updated_at' => now(),
            ];
            if ($row) {
                $inventory->table('packing_materials')->where('id', $row->id)->update($values);
            } else {
                $inventory->table('packing_materials')->insert($values + [
                    'client_id' => $clientId,
                    'name' => $meta['name'],
                    'low_stock_threshold' => 5,
                    'created_at' => now(),
                ]);
            }
            $updated++;
        }

        $this->info("Synchronized {$updated} packing materials for client {$clientId}.");

        return self::SUCCESS;
    }
}
