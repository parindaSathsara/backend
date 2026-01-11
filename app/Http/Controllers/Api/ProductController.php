<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

/**
 * Product Controller
 * Handles public product browsing
 */
class ProductController extends Controller
{
    /**
     * Get all products with filters
     */
    public function index(Request $request)
    {
        $query = Product::query()->active()->with(['primaryImage', 'category']);

        // Filter by category
        if ($request->has('category')) {
            $query->whereHas('category', function ($q) use ($request) {
                $q->where('slug', $request->category);
            });
        }

        // Filter by price range
        if ($request->has('min_price')) {
            $query->where('price', '>=', $request->min_price);
        }

        if ($request->has('max_price')) {
            $query->where('price', '<=', $request->max_price);
        }

        // Filter by featured/trending
        if ($request->boolean('featured')) {
            $query->featured();
        }

        if ($request->boolean('trending')) {
            $query->trending();
        }

        // Filter by on sale
        if ($request->boolean('on_sale')) {
            $query->onSale();
        }

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        // Sorting - support both 'sort' shorthand and 'sort_by'/'sort_order'
        $sort = $request->get('sort');
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        
        // Handle shorthand sort values
        if ($sort) {
            switch ($sort) {
                case 'newest':
                    $sortBy = 'created_at';
                    $sortOrder = 'desc';
                    break;
                case 'oldest':
                    $sortBy = 'created_at';
                    $sortOrder = 'asc';
                    break;
                case 'price_low':
                    $sortBy = 'price';
                    $sortOrder = 'asc';
                    break;
                case 'price_high':
                    $sortBy = 'price';
                    $sortOrder = 'desc';
                    break;
                case 'name_asc':
                    $sortBy = 'name';
                    $sortOrder = 'asc';
                    break;
                case 'name_desc':
                    $sortBy = 'name';
                    $sortOrder = 'desc';
                    break;
            }
        }
        
        if ($sortBy === 'price') {
            $query->orderBy('price', $sortOrder);
        } elseif ($sortBy === 'name') {
            $query->orderBy('name', $sortOrder);
        } else {
            $query->orderBy('created_at', $sortOrder);
        }

        $products = $query->paginate($request->get('per_page', 20));

        return response()->json($products);
    }

    /**
     * Get featured products
     */
    public function featured()
    {
        $products = Product::active()
            ->featured()
            ->with(['primaryImage', 'category'])
            ->limit(8)
            ->get();

        return response()->json([
            'products' => $products
        ]);
    }

    /**
     * Get trending products
     */
    public function trending()
    {
        $products = Product::active()
            ->trending()
            ->with(['primaryImage', 'category'])
            ->limit(8)
            ->get();

        return response()->json([
            'products' => $products
        ]);
    }

    /**
     * Get single product
     */
    public function show(string $slug)
    {
        $product = Product::where('slug', $slug)
            ->active()
            ->with([
                'category',
                'images',
                'variants.inventory',
                'inventory',
                'approvedReviews.user'
            ])
            ->firstOrFail();

        // Add average rating and review count
        $product->average_rating = $product->approvedReviews()->avg('rating') ?? 0;
        $product->review_count = $product->approvedReviews()->count();

        return response()->json([
            'product' => $product
        ]);
    }
}
