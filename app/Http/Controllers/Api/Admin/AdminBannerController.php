<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AdminBannerController extends Controller
{
    /**
     * Display a listing of banners
     */
    public function index(Request $request)
    {
        $query = Banner::query();

        // Filter by status
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // Filter by position
        if ($request->has('position')) {
            $query->where('position', $request->position);
        }

        // Sorting
        $query->orderBy('sort_order', 'asc');

        return response()->json(['banners' => $query->get()]);
    }

    /**
     * Store a newly created banner
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'subtitle' => 'nullable|string|max:500',
            'image' => 'required|image|mimes:jpeg,png,jpg,webp|max:5120',
            'link_url' => 'nullable|string|max:255',
            'button_text' => 'nullable|string|max:50',
            'position' => 'nullable|string|in:home_hero,home_secondary,category,promotion',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after:starts_at',
        ]);

        // Handle image upload
        $validated['image_path'] = $request->file('image')->store('banners', 'public');

        $banner = Banner::create($validated);

        return response()->json([
            'message' => 'Banner created successfully',
            'banner' => $banner
        ], 201);
    }

    /**
     * Display the specified banner
     */
    public function show(Banner $banner)
    {
        return response()->json(['banner' => $banner]);
    }

    /**
     * Update the specified banner
     */
    public function update(Request $request, Banner $banner)
    {
        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'subtitle' => 'nullable|string|max:500',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
            'link_url' => 'nullable|string|max:255',
            'button_text' => 'nullable|string|max:50',
            'position' => 'nullable|string|in:home_hero,home_secondary,category,promotion',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after:starts_at',
        ]);

        // Handle image upload
        if ($request->hasFile('image')) {
            // Delete old image
            if ($banner->image_path) {
                Storage::disk('public')->delete($banner->image_path);
            }
            $validated['image_path'] = $request->file('image')->store('banners', 'public');
        }

        $banner->update($validated);

        return response()->json([
            'message' => 'Banner updated successfully',
            'banner' => $banner->fresh()
        ]);
    }

    /**
     * Remove the specified banner
     */
    public function destroy(Banner $banner)
    {
        if ($banner->image_path) {
            Storage::disk('public')->delete($banner->image_path);
        }
        
        $banner->delete();

        return response()->json([
            'message' => 'Banner deleted successfully'
        ]);
    }

    /**
     * Toggle banner active status
     */
    public function toggleStatus(Banner $banner)
    {
        $banner->update(['is_active' => !$banner->is_active]);

        return response()->json([
            'message' => 'Banner status updated',
            'is_active' => $banner->is_active
        ]);
    }
}
