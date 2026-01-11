<?php

namespace Database\Seeders;

use App\Models\Album;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AlbumSeeder extends Seeder
{
    public function run(): void
    {
        $albums = [
            [
                'name' => 'Bridal Collection 2026',
                'description' => 'Exquisite bridal wear collection featuring stunning sarees, jewelry sets, and accessories for the modern bride.',
                'cover_image' => 'https://images.unsplash.com/photo-1610030469983-98e550d6193c?w=1200&q=80',
                'is_featured' => true,
            ],
            [
                'name' => 'Summer Elegance',
                'description' => 'Light and breezy collection perfect for summer occasions. Featuring pastel sarees and delicate jewelry.',
                'cover_image' => 'https://images.unsplash.com/photo-1617627143750-d86bc21e42bb?w=1200&q=80',
                'is_featured' => true,
            ],
            [
                'name' => 'Festival Favorites',
                'description' => 'Celebrate every festival in style with our curated collection of vibrant sarees and traditional jewelry.',
                'cover_image' => 'https://images.unsplash.com/photo-1583391733956-3750e0ff4e8b?w=1200&q=80',
                'is_featured' => true,
            ],
            [
                'name' => 'Office Chic',
                'description' => 'Professional yet elegant pieces for the working woman. Subtle colors and sophisticated designs.',
                'cover_image' => 'https://images.unsplash.com/photo-1594938298603-c8148c4dae35?w=1200&q=80',
                'is_featured' => false,
            ],
            [
                'name' => 'Heritage Gold',
                'description' => 'Timeless gold jewelry inspired by ancient Indian heritage. Crafted for generations.',
                'cover_image' => 'https://images.unsplash.com/photo-1515562141207-7a88fb7ce338?w=1200&q=80',
                'is_featured' => true,
            ],
            [
                'name' => 'Contemporary Fusion',
                'description' => 'Where tradition meets modernity. Indo-western styles for the global Indian woman.',
                'cover_image' => 'https://images.unsplash.com/photo-1573408301185-9146fe634ad0?w=1200&q=80',
                'is_featured' => false,
            ],
        ];

        foreach ($albums as $albumData) {
            $album = Album::create([
                'name' => $albumData['name'],
                'slug' => Str::slug($albumData['name']),
                'description' => $albumData['description'],
                'cover_image' => $albumData['cover_image'],
                'is_active' => true,
                'is_featured' => $albumData['is_featured'],
            ]);

            // Attach random products to each album
            $products = Product::inRandomOrder()->take(rand(3, 8))->pluck('id');
            $album->products()->attach($products);
        }
    }
}
