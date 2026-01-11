<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

/**
 * Category Seeder
 * Seeds initial product categories
 */
class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Sarees
        $sarees = Category::create([
            'name' => 'Sarees',
            'slug' => 'sarees',
            'description' => 'Beautiful traditional and modern sarees',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        // Saree subcategories
        Category::create([
            'name' => 'Silk Sarees',
            'slug' => 'silk-sarees',
            'parent_id' => $sarees->id,
            'is_active' => true,
        ]);

        Category::create([
            'name' => 'Cotton Sarees',
            'slug' => 'cotton-sarees',
            'parent_id' => $sarees->id,
            'is_active' => true,
        ]);

        // Shirts
        $shirts = Category::create([
            'name' => 'Shirts',
            'slug' => 'shirts',
            'description' => 'Stylish shirts for every occasion',
            'is_active' => true,
            'sort_order' => 2,
        ]);

        // Jewelry
        $jewelry = Category::create([
            'name' => 'Jewelry',
            'slug' => 'jewelry',
            'description' => 'Exquisite jewelry collection',
            'is_active' => true,
            'sort_order' => 3,
        ]);

        // Jewelry subcategories
        $subcategories = [
            'Bangles',
            'Cuff Bracelets',
            'Earrings',
            'Anklets',
            'Rings',
            'Jewelry Sets',
            'Kids Jewelry',
            'Nose Studs',
            'Toe Rings',
            'Necklaces',
        ];

        foreach ($subcategories as $index => $subcat) {
            Category::create([
                'name' => $subcat,
                'slug' => strtolower(str_replace(' ', '-', $subcat)),
                'parent_id' => $jewelry->id,
                'is_active' => true,
                'sort_order' => $index + 1,
            ]);
        }
    }
}
