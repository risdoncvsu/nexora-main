<?php

namespace Modules\Inventory\Services;

class PackingMaterialCatalog
{
    /**
     * Map received Inventory item names to the packing records consumed by
     * Order Fulfillment. Non-packing inventory remains outside this catalog.
     */
    public static function definition(string $name): ?array
    {
        $normalised = strtolower(trim($name));

        $definitions = [
            'foam inserts' => ['name' => 'Foam Inserts', 'is_box' => false, 'box_size' => null],
            'foam insert' => ['name' => 'Foam Inserts', 'is_box' => false, 'box_size' => null],
            'silica gel packs' => ['name' => 'Silica Gel Packs', 'is_box' => false, 'box_size' => null],
            'silica gel' => ['name' => 'Silica Gel Packs', 'is_box' => false, 'box_size' => null],
            'silica gels' => ['name' => 'Silica Gel Packs', 'is_box' => false, 'box_size' => null],
            'gel' => ['name' => 'Silica Gel Packs', 'is_box' => false, 'box_size' => null],
            'gels' => ['name' => 'Silica Gel Packs', 'is_box' => false, 'box_size' => null],
            'bubble wrap' => ['name' => 'Bubble Wrap', 'is_box' => false, 'box_size' => null],
            'bubble wraps' => ['name' => 'Bubble Wrap', 'is_box' => false, 'box_size' => null],
            'packing tape' => ['name' => 'Packing Tape', 'is_box' => false, 'box_size' => null],
            'packing tapes' => ['name' => 'Packing Tape', 'is_box' => false, 'box_size' => null],
            'fragile tape' => ['name' => 'Fragile Tape', 'is_box' => false, 'box_size' => null],
            'fragile tapes' => ['name' => 'Fragile Tape', 'is_box' => false, 'box_size' => null],
        ];

        if (isset($definitions[$normalised])) {
            return $definitions[$normalised];
        }

        if (str_contains($normalised, 'box')) {
            return [
                'name' => trim($name),
                'is_box' => true,
                'box_size' => trim($name),
            ];
        }

        return null;
    }
}
