<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\Inventory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AdminProductController extends Controller
{
    /**
     * Display a listing of products
     */
    public function index(Request $request)
    {
        $query = Product::with(['category', 'images', 'inventory']);

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Filter by category
        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
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
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $perPage = $request->get('per_page', 15);
        $products = $query->paginate($perPage);

        return response()->json($products);
    }

    /**
     * Store a newly created product
     */
    public function store(Request $request)
    {
        \Log::info('Product creation request', ['data' => $request->all()]);
        
        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'short_description' => 'nullable|string|max:500',
            'price' => 'required|numeric|min:0',
            'sale_price' => 'nullable|numeric|min:0',
            'cost_price' => 'nullable|numeric|min:0',
            'weight' => 'nullable|numeric|min:0',
            'sku' => 'nullable|string|unique:products,sku',
            'is_featured' => 'nullable|boolean',
            'is_trending' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
            'meta_keywords' => 'nullable|string',
            'stock_quantity' => 'nullable|integer|min:0',
            'low_stock_threshold' => 'nullable|integer|min:0',
        ]);

        DB::beginTransaction();
        try {
            $product = Product::create($validated);

            // Create inventory record
            Inventory::create([
                'product_id' => $product->id,
                'quantity' => $validated['stock_quantity'] ?? 0,
                'low_stock_threshold' => $validated['low_stock_threshold'] ?? 10,
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Product created successfully',
                'product' => $product->load(['category', 'images', 'inventory'])
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error creating product', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified product
     */
    public function show(Product $product)
    {
        return response()->json([
            'product' => $product->load([
                'category', 
                'images', 
                'variants.variationOptions.variationType', 
                'variants.inventory',
                'inventory'
            ])
        ]);
    }

    /**
     * Update the specified product
     */
    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'category_id' => 'sometimes|exists:categories,id',
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'short_description' => 'nullable|string|max:500',
            'price' => 'sometimes|numeric|min:0',
            'sale_price' => 'nullable|numeric|min:0',
            'cost_price' => 'nullable|numeric|min:0',
            'weight' => 'nullable|numeric|min:0',
            'sku' => 'nullable|string|unique:products,sku,' . $product->id,
            'is_featured' => 'boolean',
            'is_trending' => 'boolean',
            'is_active' => 'boolean',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
            'meta_keywords' => 'nullable|string',
        ]);

        $product->update($validated);

        // Update inventory if provided
        if ($request->has('stock_quantity')) {
            $product->inventory()->updateOrCreate(
                ['product_id' => $product->id],
                [
                    'quantity' => $request->stock_quantity,
                    'low_stock_threshold' => $request->low_stock_threshold ?? 10
                ]
            );
        }

        return response()->json([
            'message' => 'Product updated successfully',
            'product' => $product->fresh(['category', 'images', 'inventory'])
        ]);
    }

    /**
     * Remove the specified product
     */
    public function destroy(Product $product)
    {
        $product->delete();

        return response()->json([
            'message' => 'Product deleted successfully'
        ]);
    }

    /**
     * Toggle product active status
     */
    public function toggleStatus(Product $product)
    {
        $product->update(['is_active' => !$product->is_active]);

        return response()->json([
            'message' => 'Product status updated',
            'is_active' => $product->is_active
        ]);
    }

    /**
     * Toggle featured status
     */
    public function toggleFeatured(Product $product)
    {
        $product->update(['is_featured' => !$product->is_featured]);

        return response()->json([
            'message' => 'Featured status updated',
            'is_featured' => $product->is_featured
        ]);
    }

    /**
     * Toggle trending status
     */
    public function toggleTrending(Product $product)
    {
        $product->update(['is_trending' => !$product->is_trending]);

        return response()->json([
            'message' => 'Trending status updated',
            'is_trending' => $product->is_trending
        ]);
    }

    /**
     * Upload product images
     */
    public function uploadImages(Request $request, Product $product)
    {
        $request->validate([
            'images' => 'required|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,webp|max:5120',
        ]);

        $uploadedImages = [];

        foreach ($request->file('images') as $index => $image) {
            $path = $image->store('products', 'public');
            
            $productImage = ProductImage::create([
                'product_id' => $product->id,
                'image_path' => $path,
                'alt_text' => $product->name,
                'is_primary' => $product->images()->count() === 0 && $index === 0,
                'sort_order' => $product->images()->count() + $index,
            ]);

            $uploadedImages[] = $productImage;
        }

        return response()->json([
            'message' => 'Images uploaded successfully',
            'images' => $uploadedImages
        ]);
    }

    /**
     * Delete a product image
     */
    public function deleteImage(Product $product, ProductImage $image)
    {
        if ($image->product_id !== $product->id) {
            return response()->json(['message' => 'Image not found'], 404);
        }

        Storage::disk('public')->delete($image->image_path);
        $image->delete();

        return response()->json([
            'message' => 'Image deleted successfully'
        ]);
    }

    /**
     * Add product variant
     */
    public function addVariant(Request $request, Product $product)
    {
        $validated = $request->validate([
            'variant_name' => 'nullable|string|max:255',
            'price_adjustment' => 'nullable|numeric',
            'sku' => 'nullable|string|unique:product_variants,sku',
            'is_active' => 'boolean',
            'variation_option_ids' => 'nullable|array',
            'variation_option_ids.*' => 'exists:variation_options,id',
            'stock_quantity' => 'nullable|integer|min:0',
        ]);

        DB::beginTransaction();
        try {
            // Create variant
            $variant = $product->variants()->create([
                'variant_name' => $validated['variant_name'] ?? null,
                'price_adjustment' => $validated['price_adjustment'] ?? 0,
                'sku' => $validated['sku'] ?? null,
                'is_active' => $validated['is_active'] ?? true,
            ]);

            // Attach variation options if provided
            if (!empty($validated['variation_option_ids'])) {
                $optionIds = $validated['variation_option_ids'];
                $options = \App\Models\VariationOption::whereIn('id', $optionIds)->get();
                
                $pivotData = [];
                $nameParts = [];
                foreach ($options as $option) {
                    $pivotData[$option->id] = ['variation_type_id' => $option->variation_type_id];
                    $nameParts[] = $option->name;
                }
                $variant->variationOptions()->attach($pivotData);
                
                // Auto-generate variant name if not provided
                if (empty($validated['variant_name']) && !empty($nameParts)) {
                    $variant->update(['variant_name' => implode(' / ', $nameParts)]);
                }
            }

            // Create inventory for variant if stock provided
            if (isset($validated['stock_quantity'])) {
                Inventory::create([
                    'product_id' => $product->id,
                    'variant_id' => $variant->id,
                    'quantity' => $validated['stock_quantity'],
                    'low_stock_threshold' => 10,
                ]);
            }

            DB::commit();

            return response()->json([
                'message' => 'Variant added successfully',
                'variant' => $variant->load('variationOptions.variationType', 'inventory')
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error adding variant', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Update product variant
     */
    public function updateVariant(Request $request, Product $product, ProductVariant $variant)
    {
        if ($variant->product_id !== $product->id) {
            return response()->json(['message' => 'Variant not found'], 404);
        }

        $validated = $request->validate([
            'variant_name' => 'nullable|string|max:255',
            'price_adjustment' => 'nullable|numeric',
            'sku' => 'nullable|string|unique:product_variants,sku,' . $variant->id,
            'is_active' => 'boolean',
            'variation_option_ids' => 'nullable|array',
            'variation_option_ids.*' => 'exists:variation_options,id',
            'stock_quantity' => 'nullable|integer|min:0',
        ]);

        DB::beginTransaction();
        try {
            // Update basic variant info
            $variant->update([
                'variant_name' => $validated['variant_name'] ?? $variant->variant_name,
                'price_adjustment' => $validated['price_adjustment'] ?? $variant->price_adjustment,
                'sku' => $validated['sku'] ?? $variant->sku,
                'is_active' => $validated['is_active'] ?? $variant->is_active,
            ]);

            // Update variation options if provided
            if (isset($validated['variation_option_ids'])) {
                $optionIds = $validated['variation_option_ids'];
                $options = \App\Models\VariationOption::whereIn('id', $optionIds)->get();
                
                $pivotData = [];
                $nameParts = [];
                foreach ($options as $option) {
                    $pivotData[$option->id] = ['variation_type_id' => $option->variation_type_id];
                    $nameParts[] = $option->name;
                }
                $variant->variationOptions()->sync($pivotData);
                
                // Auto-update variant name if not explicitly set
                if (empty($validated['variant_name']) && !empty($nameParts)) {
                    $variant->update(['variant_name' => implode(' / ', $nameParts)]);
                }
            }

            // Update inventory if stock provided
            if (isset($validated['stock_quantity'])) {
                $variant->inventory()->updateOrCreate(
                    ['variant_id' => $variant->id],
                    [
                        'product_id' => $product->id,
                        'quantity' => $validated['stock_quantity'],
                    ]
                );
            }

            DB::commit();

            return response()->json([
                'message' => 'Variant updated successfully',
                'variant' => $variant->fresh('variationOptions.variationType', 'inventory')
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error updating variant', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Delete product variant
     */
    public function deleteVariant(Product $product, ProductVariant $variant)
    {
        if ($variant->product_id !== $product->id) {
            return response()->json(['message' => 'Variant not found'], 404);
        }

        $variant->delete();

        return response()->json([
            'message' => 'Variant deleted successfully'
        ]);
    }
}
