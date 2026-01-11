<?php

namespace Database\Seeders;

use App\Models\VariationOption;
use Illuminate\Database\Seeder;

/**
 * Seeds default variation options (colors, sizes, materials)
 */
class VariationOptionSeeder extends Seeder
{
    public function run(): void
    {
        // Colors
        $colors = [
            ['name' => 'Red', 'value' => '#DC2626'],
            ['name' => 'Blue', 'value' => '#2563EB'],
            ['name' => 'Green', 'value' => '#16A34A'],
            ['name' => 'Black', 'value' => '#000000'],
            ['name' => 'White', 'value' => '#FFFFFF'],
            ['name' => 'Gold', 'value' => '#D4AF37'],
            ['name' => 'Silver', 'value' => '#C0C0C0'],
            ['name' => 'Pink', 'value' => '#EC4899'],
            ['name' => 'Purple', 'value' => '#9333EA'],
            ['name' => 'Orange', 'value' => '#F97316'],
            ['name' => 'Yellow', 'value' => '#EAB308'],
            ['name' => 'Navy', 'value' => '#1E3A8A'],
            ['name' => 'Maroon', 'value' => '#7F1D1D'],
            ['name' => 'Beige', 'value' => '#D4C5A9'],
            ['name' => 'Cream', 'value' => '#FFFDD0'],
            ['name' => 'Brown', 'value' => '#78350F'],
            ['name' => 'Teal', 'value' => '#0D9488'],
            ['name' => 'Coral', 'value' => '#FF6B6B'],
            ['name' => 'Magenta', 'value' => '#DB2777'],
            ['name' => 'Rose Gold', 'value' => '#B76E79'],
        ];

        foreach ($colors as $index => $color) {
            VariationOption::create([
                'type' => 'color',
                'name' => $color['name'],
                'value' => $color['value'],
                'display_order' => $index,
                'is_active' => true,
            ]);
        }

        // Sizes
        $sizes = [
            ['name' => 'XS', 'value' => 'Extra Small'],
            ['name' => 'S', 'value' => 'Small'],
            ['name' => 'M', 'value' => 'Medium'],
            ['name' => 'L', 'value' => 'Large'],
            ['name' => 'XL', 'value' => 'Extra Large'],
            ['name' => 'XXL', 'value' => '2X Large'],
            ['name' => 'XXXL', 'value' => '3X Large'],
            ['name' => 'Free Size', 'value' => 'One Size Fits All'],
            ['name' => '32', 'value' => 'Size 32'],
            ['name' => '34', 'value' => 'Size 34'],
            ['name' => '36', 'value' => 'Size 36'],
            ['name' => '38', 'value' => 'Size 38'],
            ['name' => '40', 'value' => 'Size 40'],
            ['name' => '42', 'value' => 'Size 42'],
            ['name' => '44', 'value' => 'Size 44'],
        ];

        foreach ($sizes as $index => $size) {
            VariationOption::create([
                'type' => 'size',
                'name' => $size['name'],
                'value' => $size['value'],
                'display_order' => $index,
                'is_active' => true,
            ]);
        }

        // Materials
        $materials = [
            ['name' => 'Cotton', 'value' => '100% Cotton'],
            ['name' => 'Silk', 'value' => 'Pure Silk'],
            ['name' => 'Chiffon', 'value' => 'Chiffon Fabric'],
            ['name' => 'Georgette', 'value' => 'Georgette Fabric'],
            ['name' => 'Linen', 'value' => 'Linen Fabric'],
            ['name' => 'Polyester', 'value' => 'Polyester Blend'],
            ['name' => 'Rayon', 'value' => 'Rayon Fabric'],
            ['name' => 'Velvet', 'value' => 'Velvet Material'],
            ['name' => 'Net', 'value' => 'Net/Lace'],
            ['name' => 'Satin', 'value' => 'Satin Finish'],
            ['name' => 'Crepe', 'value' => 'Crepe Fabric'],
            ['name' => 'Banarasi', 'value' => 'Banarasi Weave'],
            ['name' => 'Kanjivaram', 'value' => 'Kanjivaram Silk'],
            ['name' => 'Organza', 'value' => 'Organza Fabric'],
            ['name' => 'Khadi', 'value' => 'Khadi Cotton'],
            ['name' => 'Sterling Silver', 'value' => '925 Sterling Silver'],
            ['name' => 'Gold Plated', 'value' => '22K Gold Plated'],
            ['name' => 'Oxidized', 'value' => 'Oxidized Metal'],
            ['name' => 'Pearl', 'value' => 'Cultured Pearl'],
            ['name' => 'Kundan', 'value' => 'Kundan Setting'],
        ];

        foreach ($materials as $index => $material) {
            VariationOption::create([
                'type' => 'material',
                'name' => $material['name'],
                'value' => $material['value'],
                'display_order' => $index,
                'is_active' => true,
            ]);
        }
    }
}
