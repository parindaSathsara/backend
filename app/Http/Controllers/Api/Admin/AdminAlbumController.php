<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Album;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AdminAlbumController extends Controller
{
    /**
     * Display a listing of albums
     */
    public function index(Request $request)
    {
        $query = Album::withCount('products');

        // Search
        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        // Filter by status
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // Filter by featured
        if ($request->has('is_featured')) {
            $query->where('is_featured', $request->boolean('is_featured'));
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'sort_order');
        $sortOrder = $request->get('sort_order', 'asc');
        $query->orderBy($sortBy, $sortOrder);

        $perPage = $request->get('per_page', 15);
        return response()->json($query->paginate($perPage));
    }

    /**
     * Store a newly created album
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'price' => 'nullable|numeric|min:0',
            'discount_percentage' => 'nullable|numeric|min:0|max:100',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'is_featured' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer',
            'products' => 'nullable|string', // JSON string of products
        ]);

        $validated['slug'] = $validated['slug'] ?? Str::slug($validated['name']);

        // Handle image upload
        if ($request->hasFile('image')) {
            $validated['cover_image'] = $request->file('image')->store('albums', 'public');
        }

        // Remove products from validated data before creating album
        $productsJson = $validated['products'] ?? null;
        unset($validated['products']);

        $album = Album::create($validated);

        // Attach products if provided
        if ($productsJson) {
            $products = json_decode($productsJson, true);
            if (is_array($products)) {
                $syncData = [];
                foreach ($products as $product) {
                    $syncData[$product['product_id']] = [
                        'variant_id' => $product['variant_id'] ?? null,
                        'quantity' => $product['quantity'] ?? 1,
                        'sort_order' => $product['sort_order'] ?? 0,
                    ];
                }
                $album->products()->sync($syncData);
            }
        }

        return response()->json([
            'message' => 'Album created successfully',
            'album' => $album->load('products.primaryImage')
        ], 201);
    }

    /**
     * Display the specified album
     */
    public function show(Album $album)
    {
        return response()->json([
            'album' => $album->load(['products.primaryImage', 'products.variants'])->loadCount('products')
        ]);
    }

    /**
     * Update the specified album
     */
    public function update(Request $request, Album $album)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'slug' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'price' => 'nullable|numeric|min:0',
            'discount_percentage' => 'nullable|numeric|min:0|max:100',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'is_featured' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer',
            'products' => 'nullable|string', // JSON string of products
        ]);

        if (isset($validated['name']) && !isset($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        // Handle image upload
        if ($request->hasFile('image')) {
            $validated['cover_image'] = $request->file('image')->store('albums', 'public');
        }

        // Handle products separately
        $productsJson = $validated['products'] ?? null;
        unset($validated['products']);

        $album->update($validated);

        // Sync products if provided
        if ($productsJson) {
            $products = json_decode($productsJson, true);
            if (is_array($products)) {
                $syncData = [];
                foreach ($products as $product) {
                    $syncData[$product['product_id']] = [
                        'variant_id' => $product['variant_id'] ?? null,
                        'quantity' => $product['quantity'] ?? 1,
                        'sort_order' => $product['sort_order'] ?? 0,
                    ];
                }
                $album->products()->sync($syncData);
            }
        }

        return response()->json([
            'message' => 'Album updated successfully',
            'album' => $album->fresh()->load(['products.primaryImage', 'products.variants'])
        ]);
    }

    /**
     * Remove the specified album
     */
    public function destroy(Album $album)
    {
        // Detach all products first
        $album->products()->detach();
        $album->delete();

        return response()->json([
            'message' => 'Album deleted successfully'
        ]);
    }

    /**
     * Toggle album active status
     */
    public function toggleStatus(Album $album)
    {
        $album->update(['is_active' => !$album->is_active]);

        return response()->json([
            'message' => 'Album status updated',
            'is_active' => $album->is_active
        ]);
    }

    /**
     * Toggle featured status
     */
    public function toggleFeatured(Album $album)
    {
        $album->update(['is_featured' => !$album->is_featured]);

        return response()->json([
            'message' => 'Featured status updated',
            'is_featured' => $album->is_featured
        ]);
    }

    /**
     * Add a product to album
     */
    public function addProduct(Request $request, Album $album)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'sort_order' => 'nullable|integer',
        ]);

        $album->products()->syncWithoutDetaching([
            $validated['product_id'] => ['sort_order' => $validated['sort_order'] ?? 0]
        ]);

        return response()->json([
            'message' => 'Product added to album',
            'album' => $album->load('products')
        ]);
    }

    /**
     * Remove a product from album
     */
    public function removeProduct(Album $album, $productId)
    {
        $album->products()->detach($productId);

        return response()->json([
            'message' => 'Product removed from album',
            'album' => $album->load('products')
        ]);
    }
}
