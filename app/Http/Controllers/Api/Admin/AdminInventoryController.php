<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Inventory;
use App\Models\Product;
use Illuminate\Http\Request;

class AdminInventoryController extends Controller
{
    /**
     * Display a listing of inventory
     */
    public function index(Request $request)
    {
        $query = Inventory::with(['product.category', 'product.images', 'variant.variationOptions.variationType']);

        // Filter by stock status
        if ($request->boolean('low_stock')) {
            $query->lowStock();
        } elseif ($request->boolean('out_of_stock')) {
            $query->outOfStock();
        }

        // Search by product name
        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->whereHas('product', function ($pq) use ($search) {
                    $pq->where('name', 'like', "%{$search}%")
                      ->orWhere('sku', 'like', "%{$search}%");
                })
                ->orWhereHas('variant', function ($vq) use ($search) {
                    $vq->where('sku', 'like', "%{$search}%")
                      ->orWhere('variant_name', 'like', "%{$search}%");
                });
            });
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'quantity');
        $sortOrder = $request->get('sort_order', 'asc');
        $query->orderBy($sortBy, $sortOrder);

        $perPage = $request->get('per_page', 15);
        return response()->json($query->paginate($perPage));
    }

    /**
     * Get low stock products
     */
    public function lowStock(Request $request)
    {
        $query = Inventory::lowStock()->with(['product', 'variant.variationOptions.variationType']);

        $perPage = $request->get('per_page', 15);
        return response()->json($query->paginate($perPage));
    }

    /**
     * Get out of stock products
     */
    public function outOfStock(Request $request)
    {
        $query = Inventory::outOfStock()->with(['product', 'variant.variationOptions.variationType']);

        $perPage = $request->get('per_page', 15);
        return response()->json($query->paginate($perPage));
    }

    /**
     * Update inventory
     */
    public function update(Request $request, Inventory $inventory)
    {
        $validated = $request->validate([
            'quantity' => 'required|integer|min:0',
            'low_stock_threshold' => 'nullable|integer|min:0',
            'reserved_quantity' => 'nullable|integer|min:0',
        ]);

        $inventory->update($validated);

        return response()->json([
            'message' => 'Inventory updated successfully',
            'inventory' => $inventory->fresh(['product', 'variant.variationOptions.variationType'])
        ]);
    }

    /**
     * Adjust inventory (add or subtract)
     */
    public function adjust(Request $request, Inventory $inventory)
    {
        $validated = $request->validate([
            'adjustment' => 'required|integer',
            'reason' => 'nullable|string|max:255',
        ]);

        $newQuantity = $inventory->quantity + $validated['adjustment'];
        
        if ($newQuantity < 0) {
            return response()->json([
                'message' => 'Cannot reduce stock below 0'
            ], 422);
        }

        $inventory->update(['quantity' => $newQuantity]);

        return response()->json([
            'message' => 'Inventory adjusted successfully',
            'inventory' => $inventory->fresh(['product', 'variant.variationOptions.variationType']),
            'adjustment' => $validated['adjustment'],
            'previous_quantity' => $inventory->quantity - $validated['adjustment'],
            'new_quantity' => $newQuantity
        ]);
    }
}