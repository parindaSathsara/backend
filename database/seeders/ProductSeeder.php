<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Category;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\Inventory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $products = [
            // Sarees
            [
                'name' => 'Banarasi Silk Saree - Royal Red',
                'description' => 'Exquisite handwoven Banarasi silk saree with intricate gold zari work. Perfect for weddings and special occasions.',
                'price' => 15999,
                'discount_price' => 13999,
                'category' => 'sarees',
                'is_featured' => true,
                'is_trending' => true,
                'image' => 'https://images.unsplash.com/photo-1610030469983-98e550d6193c?w=800&q=80',
            ],
            [
                'name' => 'Kanjivaram Pure Silk Saree',
                'description' => 'Authentic Kanjivaram saree with traditional temple border and rich pallu design.',
                'price' => 24999,
                'discount_price' => null,
                'category' => 'sarees',
                'is_featured' => true,
                'is_trending' => false,
                'image' => 'https://images.unsplash.com/photo-1583391733956-3750e0ff4e8b?w=800&q=80',
            ],
            [
                'name' => 'Chanderi Cotton Silk Saree',
                'description' => 'Lightweight Chanderi saree with delicate butis and elegant border. Ideal for office and casual occasions.',
                'price' => 4999,
                'discount_price' => 3999,
                'category' => 'sarees',
                'is_featured' => false,
                'is_trending' => true,
                'image' => 'https://images.unsplash.com/photo-1617627143750-d86bc21e42bb?w=800&q=80',
            ],
            [
                'name' => 'Patola Double Ikat Saree',
                'description' => 'Rare Patola saree featuring traditional geometric patterns in vibrant colors.',
                'price' => 45000,
                'discount_price' => null,
                'category' => 'sarees',
                'is_featured' => true,
                'is_trending' => false,
                'image' => 'https://images.unsplash.com/photo-1583391733981-8b530c6d14c6?w=800&q=80',
            ],
            [
                'name' => 'Organza Floral Print Saree',
                'description' => 'Modern organza saree with beautiful floral prints and sequin work.',
                'price' => 6999,
                'discount_price' => 5499,
                'category' => 'sarees',
                'is_featured' => false,
                'is_trending' => true,
                'image' => 'https://images.unsplash.com/photo-1594938328870-9623159c8c99?w=800&q=80',
            ],

            // Jewelry
            [
                'name' => 'Kundan Bridal Necklace Set',
                'description' => 'Stunning bridal kundan necklace with matching earrings and maang tikka. Crafted with precision.',
                'price' => 35000,
                'discount_price' => 29999,
                'category' => 'jewelry',
                'is_featured' => true,
                'is_trending' => true,
                'image' => 'https://images.unsplash.com/photo-1515562141207-7a88fb7ce338?w=800&q=80',
            ],
            [
                'name' => 'Temple Gold Jhumkas',
                'description' => 'Traditional temple design gold-plated jhumkas with intricate craftsmanship.',
                'price' => 8999,
                'discount_price' => null,
                'category' => 'jewelry',
                'is_featured' => true,
                'is_trending' => false,
                'image' => 'https://images.unsplash.com/photo-1573408301185-9146fe634ad0?w=800&q=80',
            ],
            [
                'name' => 'Pearl Choker Necklace',
                'description' => 'Elegant freshwater pearl choker with gold accents. Perfect for any occasion.',
                'price' => 12999,
                'discount_price' => 9999,
                'category' => 'jewelry',
                'is_featured' => false,
                'is_trending' => true,
                'image' => 'https://images.unsplash.com/photo-1599643478518-a784e5dc4c8f?w=800&q=80',
            ],
            [
                'name' => 'Diamond Studded Bangles',
                'description' => 'Set of 4 exquisite bangles studded with American diamonds in gold finish.',
                'price' => 18999,
                'discount_price' => null,
                'category' => 'jewelry',
                'is_featured' => true,
                'is_trending' => false,
                'image' => 'https://images.unsplash.com/photo-1611591437281-460bfbe1220a?w=800&q=80',
            ],
            [
                'name' => 'Meenakari Hoop Earrings',
                'description' => 'Colorful meenakari work on traditional hoop earrings with antique finish.',
                'price' => 3999,
                'discount_price' => 2999,
                'category' => 'jewelry',
                'is_featured' => false,
                'is_trending' => true,
                'image' => 'https://images.unsplash.com/photo-1635767798638-3e25273a8236?w=800&q=80',
            ],

            // Shirts
            [
                'name' => 'Silk Blend Designer Shirt',
                'description' => 'Premium silk blend shirt with subtle shimmer. Perfect for formal and festive occasions.',
                'price' => 4999,
                'discount_price' => 3999,
                'category' => 'shirts',
                'is_featured' => true,
                'is_trending' => true,
                'image' => 'https://images.unsplash.com/photo-1594938298603-c8148c4dae35?w=800&q=80',
            ],
            [
                'name' => 'Embroidered Cotton Kurti',
                'description' => 'Hand-embroidered cotton kurti with mirror work and traditional patterns.',
                'price' => 2499,
                'discount_price' => null,
                'category' => 'shirts',
                'is_featured' => true,
                'is_trending' => false,
                'image' => 'https://images.unsplash.com/photo-1583391733956-6a1e6a5a6b8b?w=800&q=80',
            ],
            [
                'name' => 'Block Print Tunic Top',
                'description' => 'Handcrafted block print tunic with three-quarter sleeves and side slits.',
                'price' => 1999,
                'discount_price' => 1499,
                'category' => 'shirts',
                'is_featured' => false,
                'is_trending' => true,
                'image' => 'https://images.unsplash.com/photo-1551163943-3f7e29e0ae38?w=800&q=80',
            ],
            [
                'name' => 'Chikankari White Kurta',
                'description' => 'Lucknowi Chikankari hand-embroidered kurta in pure cotton. Timeless elegance.',
                'price' => 5999,
                'discount_price' => null,
                'category' => 'shirts',
                'is_featured' => true,
                'is_trending' => false,
                'image' => 'https://images.unsplash.com/photo-1596755094514-f87e34085b2c?w=800&q=80',
            ],
            [
                'name' => 'Bandhani Print Blouse',
                'description' => 'Vibrant bandhani print blouse with modern cut and traditional appeal.',
                'price' => 1799,
                'discount_price' => 1299,
                'category' => 'shirts',
                'is_featured' => false,
                'is_trending' => true,
                'image' => 'https://images.unsplash.com/photo-1564584217132-2271feaeb3c5?w=800&q=80',
            ],
        ];

        foreach ($products as $productData) {
            $category = Category::where('slug', $productData['category'])->first();
            
            if (!$category) {
                continue;
            }

            $product = Product::create([
                'name' => $productData['name'],
                'slug' => Str::slug($productData['name']),
                'description' => $productData['description'],
                'price' => $productData['price'],
                'sale_price' => $productData['discount_price'],
                'category_id' => $category->id,
                'is_active' => true,
                'is_featured' => $productData['is_featured'],
                'is_trending' => $productData['is_trending'],
                'sku' => 'SKU-' . strtoupper(Str::random(8)),
            ]);

            // Create product image
            ProductImage::create([
                'product_id' => $product->id,
                'image_path' => $productData['image'],
                'alt_text' => $productData['name'],
                'is_primary' => true,
                'sort_order' => 0,
            ]);

            // Create inventory
            Inventory::create([
                'product_id' => $product->id,
                'quantity' => rand(10, 100),
                'low_stock_threshold' => 5,
            ]);
        }
    }
}
