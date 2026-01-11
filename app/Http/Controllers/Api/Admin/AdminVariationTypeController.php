<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\VariationType;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AdminVariationTypeController extends Controller
{
    /**
     * Display a listing of variation types
     */
    public function index(Request $request)
    {
        $query = VariationType::withCount('options');

        // Filter by active status
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $query->orderBy('display_order');

        return response()->json($query->get());
    }

    /**
     * Store a newly created variation type
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|unique:variation_types,slug',
            'input_type' => 'required|in:select,color_picker,text',
            'is_required' => 'boolean',
            'is_active' => 'boolean',
            'display_order' => 'nullable|integer',
        ]);

        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        $variationType = VariationType::create($validated);

        return response()->json([
            'message' => 'Variation type created successfully',
            'variation_type' => $variationType
        ], 201);
    }

    /**
     * Display the specified variation type
     */
    public function show(VariationType $variationType)
    {
        $variationType->load('options');
        return response()->json($variationType);
    }

    /**
     * Update the specified variation type
     */
    public function update(Request $request, VariationType $variationType)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'slug' => 'sometimes|string|unique:variation_types,slug,' . $variationType->id,
            'input_type' => 'sometimes|in:select,color_picker,text',
            'is_required' => 'boolean',
            'is_active' => 'boolean',
            'display_order' => 'nullable|integer',
        ]);

        $variationType->update($validated);

        return response()->json([
            'message' => 'Variation type updated successfully',
            'variation_type' => $variationType->fresh()
        ]);
    }

    /**
     * Remove the specified variation type
     */
    public function destroy(VariationType $variationType)
    {
        // Check if any products are using this variation type
        $usageCount = \DB::table('product_variant_options')
            ->where('variation_type_id', $variationType->id)
            ->count();

        if ($usageCount > 0) {
            return response()->json([
                'message' => 'Cannot delete variation type that is being used by products',
                'usage_count' => $usageCount
            ], 422);
        }

        $variationType->delete();

        return response()->json([
            'message' => 'Variation type deleted successfully'
        ]);
    }

    /**
     * Toggle variation type active status
     */
    public function toggleStatus(VariationType $variationType)
    {
        $variationType->update(['is_active' => !$variationType->is_active]);

        return response()->json([
            'message' => 'Variation type status updated',
            'is_active' => $variationType->is_active
        ]);
    }

    /**
     * Reorder variation types
     */
    public function reorder(Request $request)
    {
        $validated = $request->validate([
            'orders' => 'required|array',
            'orders.*.id' => 'required|exists:variation_types,id',
            'orders.*.display_order' => 'required|integer',
        ]);

        foreach ($validated['orders'] as $order) {
            VariationType::where('id', $order['id'])
                ->update(['display_order' => $order['display_order']]);
        }

        return response()->json([
            'message' => 'Variation types reordered successfully'
        ]);
    }
}
