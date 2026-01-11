<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

/**
 * Wishlist Controller
 * Handles user wishlist operations
 */
class WishlistController extends Controller
{
    /**
     * Get user's wishlist
     */
    public function index(Request $request)
    {
        $wishlist = $request->user()
            ->wishlist()
            ->with(['primaryImage', 'category'])
            ->get();

        return response()->json([
            'wishlist' => $wishlist
        ]);
    }

    /**
     * Add product to wishlist
     */
    public function add(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
        ]);

        $product = Product::findOrFail($request->product_id);

        // Check if already in wishlist
        if ($request->user()->wishlist()->where('product_id', $product->id)->exists()) {
            return response()->json([
                'message' => 'Product already in wishlist'
            ], 400);
        }

        $request->user()->wishlist()->attach($product->id);

        return response()->json([
            'message' => 'Product added to wishlist'
        ]);
    }

    /**
     * Remove product from wishlist
     */
    public function remove(Request $request, Product $product)
    {
        $request->user()->wishlist()->detach($product->id);

        return response()->json([
            'message' => 'Product removed from wishlist'
        ]);
    }
}
