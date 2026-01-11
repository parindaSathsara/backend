<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\VariationOption;
use App\Models\VariationType;
use Illuminate\Http\Request;

/**
 * Admin Variation Options Controller
 * Manages dynamic variation option values (color options, size options, etc.)
 */
class AdminVariationController extends Controller
{
    /**
     * Get all variation options
     */
    public function index(Request $request)
    {
        $query = VariationOption::with('variationType');

        if ($request->has('variation_type_id')) {
            $query->where('variation_type_id', $request->variation_type_id);
        }

        if ($request->has('is_active')) {
            if ($request->boolean('is_active')) {
                $query->active();
            } else {
                $query->where('is_active', false);
            }
        }

        $options = $query->orderBy('variation_type_id')->orderBy('display_order')->get();

        // Group by variation type for easier frontend consumption
        $grouped = [];
        foreach ($options as $option) {
            $typeSlug = $option->variationType->slug;
            if (!isset($grouped[$typeSlug])) {
                $grouped[$typeSlug] = [
                    'type' => $option->variationType,
                    'options' => []
                ];
            }
            $grouped[$typeSlug]['options'][] = $option;
        }

        return response()->json([
            'options' => $options,
            'grouped' => $grouped
        ]);
    }

    /**
     * Get variation options by type slug (for dropdowns)
     */
    public function byType($typeSlug)
    {
        $variationType = VariationType::where('slug', $typeSlug)->first();
        
        if (!$variationType) {
            return response()->json(['message' => 'Invalid variation type'], 404);
        }

        $options = VariationOption::where('variation_type_id', $variationType->id)
            ->active()
            ->orderBy('display_order')
            ->get();

        return response()->json([
            'type' => $variationType,
            'options' => $options
        ]);
    }

    /**
     * Store a new variation option
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'variation_type_id' => 'required|exists:variation_types,id',
            'name' => 'required|string|max:100',
            'value' => 'nullable|string|max:100',
            'display_order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

        // Check for duplicate
        $exists = VariationOption::where('variation_type_id', $validated['variation_type_id'])
            ->where('name', $validated['name'])
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'An option with this name already exists for this variation type'
            ], 422);
        }

        $option = VariationOption::create($validated);
        $option->load('variationType');

        return response()->json([
            'message' => 'Variation option created successfully',
            'option' => $option
        ], 201);
    }

    /**
     * Update a variation option
     */
    public function update(Request $request, VariationOption $variation)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:100',
            'value' => 'nullable|string|max:100',
            'display_order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

        // Check for duplicate name if changing
        if (isset($validated['name']) && $validated['name'] !== $variation->name) {
            $exists = VariationOption::where('variation_type_id', $variation->variation_type_id)
                ->where('name', $validated['name'])
                ->where('id', '!=', $variation->id)
                ->exists();

            if ($exists) {
                return response()->json([
                    'message' => 'An option with this name already exists for this variation type'
                ], 422);
            }
        }

        $variation->update($validated);

        return response()->json([
            'message' => 'Variation option updated successfully',
            'option' => $variation->fresh('variationType')
        ]);
    }

    /**
     * Delete a variation option
     */
    public function destroy(VariationOption $variation)
    {
        // Check if any product variants are using this option
        $usageCount = \DB::table('product_variant_options')
            ->where('variation_option_id', $variation->id)
            ->count();

        if ($usageCount > 0) {
            return response()->json([
                'message' => 'Cannot delete variation option that is being used by product variants',
                'usage_count' => $usageCount
            ], 422);
        }

        $variation->delete();

        return response()->json([
            'message' => 'Variation option deleted successfully'
        ]);
    }

    /**
     * Toggle variation option status
     */
    public function toggleStatus(VariationOption $variation)
    {
        $variation->update(['is_active' => !$variation->is_active]);

        return response()->json([
            'message' => 'Variation option status updated',
            'is_active' => $variation->is_active
        ]);
    }

    /**
     * Reorder variation options
     */
    public function reorder(Request $request)
    {
        $validated = $request->validate([
            'orders' => 'required|array',
            'orders.*.id' => 'required|exists:variation_options,id',
            'orders.*.display_order' => 'required|integer',
        ]);

        foreach ($validated['orders'] as $order) {
            VariationOption::where('id', $order['id'])
                ->update(['display_order' => $order['display_order']]);
        }

        return response()->json([
            'message' => 'Variation options reordered successfully'
        ]);
    }
}
