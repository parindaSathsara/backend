<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Album;
use Illuminate\Http\Request;

/**
 * Album Controller
 * Handles public album browsing
 */
class AlbumController extends Controller
{
    /**
     * Get all albums
     */
    public function index(Request $request)
    {
        $query = Album::query()->active()->with('products');

        // Filter by featured
        if ($request->boolean('featured')) {
            $query->featured();
        }

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $albums = $query->orderBy('sort_order')
            ->paginate($request->get('per_page', 20));

        return response()->json($albums);
    }

    /**
     * Get featured albums
     */
    public function featured()
    {
        $albums = Album::active()
            ->featured()
            ->with('products.primaryImage')
            ->limit(6)
            ->get();

        return response()->json([
            'albums' => $albums
        ]);
    }

    /**
     * Get single album
     */
    public function show(string $slug)
    {
        $album = Album::where('slug', $slug)
            ->active()
            ->with([
                'products.primaryImage',
                'products.images',
                'products.category',
                'products.variants.inventory'
            ])
            ->firstOrFail();

        return response()->json([
            'album' => $album
        ]);
    }
}
