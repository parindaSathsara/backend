<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;

class AdminOrderController extends Controller
{
    /**
     * Display a listing of orders
     */
    public function index(Request $request)
    {
        $query = Order::with(['user', 'items.product']);

        // Search by order number or customer
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by payment status
        if ($request->has('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }

        // Filter by date range
        if ($request->has('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }
        if ($request->has('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $perPage = $request->get('per_page', 15);
        return response()->json($query->paginate($perPage));
    }

    /**
     * Display the specified order
     */
    public function show(Order $order)
    {
        return response()->json([
            'order' => $order->load(['user', 'items.product.images', 'payments'])
        ]);
    }

    /**
     * Update order status
     */
    public function updateStatus(Request $request, Order $order)
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,confirmed,processing,shipped,delivered,cancelled,refunded',
            'notes' => 'nullable|string',
        ]);

        $order->update([
            'status' => $validated['status'],
            'admin_notes' => $validated['notes'] ?? $order->admin_notes,
        ]);

        return response()->json([
            'message' => 'Order status updated successfully',
            'order' => $order->fresh()
        ]);
    }

    /**
     * Update payment status
     */
    public function updatePaymentStatus(Request $request, Order $order)
    {
        $validated = $request->validate([
            'payment_status' => 'required|in:pending,paid,failed,refunded',
        ]);

        $order->update(['payment_status' => $validated['payment_status']]);

        return response()->json([
            'message' => 'Payment status updated successfully',
            'order' => $order->fresh()
        ]);
    }

    /**
     * Add tracking information
     */
    public function addTracking(Request $request, Order $order)
    {
        $validated = $request->validate([
            'tracking_number' => 'required|string|max:255',
            'carrier' => 'nullable|string|max:100',
            'tracking_url' => 'nullable|url',
        ]);

        $order->update([
            'tracking_number' => $validated['tracking_number'],
            'carrier' => $validated['carrier'] ?? null,
            'tracking_url' => $validated['tracking_url'] ?? null,
            'status' => 'shipped',
        ]);

        return response()->json([
            'message' => 'Tracking information added successfully',
            'order' => $order->fresh()
        ]);
    }
}
