<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;

/**
 * Category Controller
 * Handles public category browsing
 */
class CategoryController extends Controller
{
    /**
     * Get all categories
     */
    public function index(Request $request)
    {
        $query = Category::query()->active();

        // Get only root categories or all
        if ($request->boolean('root_only')) {
            $query->root();
        }

        $categories = $query->with(['children' => function ($q) {
            $q->active()->orderBy('sort_order');
        }])
        ->orderBy('sort_order')
        ->get();

        return response()->json([
            'categories' => $categories
        ]);
    }

    /**
     * Get single category
     */
    public function show(string $slug)
    {
        $category = Category::where('slug', $slug)
            ->active()
            ->with(['children', 'parent'])
            ->firstOrFail();

        $products = $category->products()
            ->active()
            ->with(['primaryImage', 'category'])
            ->paginate(20);

        return response()->json([
            'category' => $category,
            'products' => $products
        ]);
    }
}
