<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Review;
use Illuminate\Http\Request;

class AdminReviewController extends Controller
{
    /**
     * Display a listing of reviews
     */
    public function index(Request $request)
    {
        $query = Review::with(['product', 'user']);

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('comment', 'like', "%{$search}%")
                  ->orWhereHas('product', function ($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%");
                  })
                  ->orWhereHas('user', function ($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%");
                  });
            });
        }

        // Filter by rating
        if ($request->has('rating')) {
            $query->where('rating', $request->rating);
        }

        // Filter by approval status
        if ($request->has('is_approved')) {
            $query->where('is_approved', $request->boolean('is_approved'));
        }

        // Filter by product
        if ($request->has('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $perPage = $request->get('per_page', 15);
        return response()->json($query->paginate($perPage));
    }

    /**
     * Display the specified review
     */
    public function show(Review $review)
    {
        return response()->json([
            'review' => $review->load(['product', 'user'])
        ]);
    }

    /**
     * Update (approve/reject) review
     */
    public function update(Request $request, Review $review)
    {
        $validated = $request->validate([
            'is_approved' => 'sometimes|boolean',
            'admin_response' => 'nullable|string',
        ]);

        $review->update($validated);

        return response()->json([
            'message' => 'Review updated successfully',
            'review' => $review->fresh(['product', 'user'])
        ]);
    }

    /**
     * Remove the specified review
     */
    public function destroy(Review $review)
    {
        $review->delete();

        return response()->json([
            'message' => 'Review deleted successfully'
        ]);
    }

    /**
     * Approve a review
     */
    public function approve(Review $review)
    {
        $review->update(['is_approved' => true]);

        return response()->json([
            'message' => 'Review approved successfully',
            'review' => $review->fresh()
        ]);
    }

    /**
     * Reject a review
     */
    public function reject(Review $review)
    {
        $review->update(['is_approved' => false]);

        return response()->json([
            'message' => 'Review rejected successfully',
            'review' => $review->fresh()
        ]);
    }

    /**
     * Get pending reviews
     */
    public function pending(Request $request)
    {
        $query = Review::with(['product', 'user'])
            ->where('is_approved', false)
            ->orderBy('created_at', 'desc');

        $perPage = $request->get('per_page', 15);
        return response()->json($query->paginate($perPage));
    }
}
