<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class VariationTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $types = [
            [
                'name' => 'Color',
                'slug' => 'color',
                'input_type' => 'color_picker',
                'is_required' => false,
                'is_active' => true,
                'display_order' => 1,
            ],
            [
                'name' => 'Size',
                'slug' => 'size',
                'input_type' => 'select',
                'is_required' => false,
                'is_active' => true,
                'display_order' => 2,
            ],
            [
                'name' => 'Material',
                'slug' => 'material',
                'input_type' => 'select',
                'is_required' => false,
                'is_active' => true,
                'display_order' => 3,
            ],
            [
                'name' => 'Gold Type',
                'slug' => 'gold_type',
                'input_type' => 'select',
                'is_required' => false,
                'is_active' => true,
                'display_order' => 4,
            ],
            [
                'name' => 'Shoe Lace Color',
                'slug' => 'shoe_lace_color',
                'input_type' => 'select',
                'is_required' => false,
                'is_active' => true,
                'display_order' => 5,
            ],
        ];

        foreach ($types as $type) {
            DB::table('variation_types')->insert([
                'name' => $type['name'],
                'slug' => $type['slug'],
                'input_type' => $type['input_type'],
                'is_required' => $type['is_required'],
                'is_active' => $type['is_active'],
                'display_order' => $type['display_order'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
