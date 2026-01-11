<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Order;
use Illuminate\Http\Request;

class AdminCustomerController extends Controller
{
    /**
     * Display a listing of customers
     */
    public function index(Request $request)
    {
        $query = User::whereHas('role', function ($q) {
            $q->where('name', 'customer');
        })->withCount('orders');

        // Search
        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        // Filter by status
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $perPage = $request->get('per_page', 15);
        return response()->json($query->paginate($perPage));
    }

    /**
     * Display the specified customer
     */
    public function show(User $customer)
    {
        $customer->load(['orders' => function ($q) {
            $q->latest()->limit(10);
        }]);

        $stats = [
            'total_orders' => $customer->orders()->count(),
            'total_spent' => $customer->orders()->where('payment_status', 'paid')->sum('total'),
            'average_order_value' => $customer->orders()->where('payment_status', 'paid')->avg('total'),
        ];

        return response()->json([
            'customer' => $customer,
            'stats' => $stats
        ]);
    }

    /**
     * Update customer status
     */
    public function update(Request $request, User $customer)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $customer->id,
            'phone' => 'nullable|string|max:20',
            'is_active' => 'boolean',
        ]);

        $customer->update($validated);

        return response()->json([
            'message' => 'Customer updated successfully',
            'customer' => $customer->fresh()
        ]);
    }

    /**
     * Toggle customer active status
     */
    public function toggleStatus(User $customer)
    {
        $customer->update(['is_active' => !$customer->is_active]);

        return response()->json([
            'message' => 'Customer status updated',
            'is_active' => $customer->is_active
        ]);
    }

    /**
     * Get customer orders
     */
    public function orders(Request $request, User $customer)
    {
        $query = $customer->orders()->with('items.product');

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $perPage = $request->get('per_page', 10);
        return response()->json($query->latest()->paginate($perPage));
    }
}
