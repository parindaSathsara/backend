<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Review Controller
 * Handles product reviews
 */
class ReviewController extends Controller
{
    /**
     * Get reviews for a product
     */
    public function index(string $slug)
    {
        $product = Product::where('slug', $slug)->firstOrFail();
        
        $reviews = Review::where('product_id', $product->id)
            ->approved()
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return response()->json($reviews);
    }

    /**
     * Create a review
     */
    public function store(Request $request, string $slug)
    {
        $product = Product::where('slug', $slug)->firstOrFail();
        
        $validator = Validator::make($request->all(), [
            'rating' => 'required|integer|min:1|max:5',
            'title' => 'nullable|string|max:255',
            'comment' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Check if user already reviewed this product
        $existingReview = Review::where('product_id', $product->id)
            ->where('user_id', $request->user()->id)
            ->first();

        if ($existingReview) {
            return response()->json([
                'message' => 'You have already reviewed this product'
            ], 400);
        }

        // Check if user purchased this product
        $hasPurchased = $request->user()
            ->orders()
            ->whereHas('items', function ($q) use ($product) {
                $q->where('product_id', $product->id);
            })
            ->where('status', 'delivered')
            ->exists();

        $review = Review::create([
            'product_id' => $product->id,
            'user_id' => $request->user()->id,
            'rating' => $request->rating,
            'title' => $request->title,
            'comment' => $request->comment,
            'is_verified_purchase' => $hasPurchased,
            'is_approved' => false, // Requires admin approval
        ]);

        return response()->json([
            'message' => 'Review submitted successfully. It will be visible after approval.',
            'review' => $review
        ], 201);
    }

    /**
     * Update a review
     */
    public function update(Request $request, Review $review)
    {
        if ($review->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'rating' => 'required|integer|min:1|max:5',
            'title' => 'nullable|string|max:255',
            'comment' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $review->update([
            'rating' => $request->rating,
            'title' => $request->title,
            'comment' => $request->comment,
            'is_approved' => false, // Reset approval status
        ]);

        return response()->json([
            'message' => 'Review updated successfully',
            'review' => $review
        ]);
    }

    /**
     * Delete a review
     */
    public function destroy(Request $request, Review $review)
    {
        if ($review->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }

        $review->delete();

        return response()->json([
            'message' => 'Review deleted successfully'
        ]);
    }
}
