<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Cart;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * Order Controller
 * Handles customer orders
 */
class OrderController extends Controller
{
    /**
     * Get user's orders
     */
    public function index(Request $request)
    {
        $orders = Order::where('user_id', $request->user()->id)
            ->with(['items.product', 'items.album'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return response()->json($orders);
    }

    /**
     * Get single order
     */
    public function show(Request $request, string $orderNumber)
    {
        $order = Order::where('order_number', $orderNumber)
            ->where('user_id', $request->user()->id)
            ->with([
                'items.product.primaryImage',
                'items.variant',
                'items.album',
                'payments'
            ])
            ->firstOrFail();

        return response()->json([
            'order' => $order
        ]);
    }

    /**
     * Create new order (checkout)
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'shipping_first_name' => 'required|string|max:255',
            'shipping_last_name' => 'required|string|max:255',
            'shipping_email' => 'required|email',
            'shipping_phone' => 'required|string',
            'shipping_address' => 'required|string',
            'shipping_city' => 'required|string',
            'shipping_state' => 'required|string',
            'shipping_postal_code' => 'required|string',
            'shipping_country' => 'required|string',
            'billing_first_name' => 'required|string|max:255',
            'billing_last_name' => 'required|string|max:255',
            'billing_email' => 'required|email',
            'billing_phone' => 'required|string',
            'billing_address' => 'required|string',
            'billing_city' => 'required|string',
            'billing_state' => 'required|string',
            'billing_postal_code' => 'required|string',
            'billing_country' => 'required|string',
            'payment_method' => 'required|in:card,bank_transfer,cod',
            'bank_reference' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->user();
        $cart = Cart::where('user_id', $user->id)->first();

        if (!$cart || $cart->items->isEmpty()) {
            return response()->json([
                'message' => 'Cart is empty'
            ], 400);
        }

        // Check stock for all items
        foreach ($cart->items as $item) {
            if (!$item->isInStock()) {
                return response()->json([
                    'message' => "Item '{$item->name}' is out of stock"
                ], 400);
            }
        }

        DB::beginTransaction();
        try {
            // Create order from cart - map field names for createFromCart
            $shippingData = [
                'first_name' => $request->shipping_first_name,
                'last_name' => $request->shipping_last_name,
                'email' => $request->shipping_email,
                'phone' => $request->shipping_phone,
                'address' => $request->shipping_address,
                'city' => $request->shipping_city,
                'state' => $request->shipping_state,
                'postal_code' => $request->shipping_postal_code,
                'country' => $request->shipping_country,
            ];

            $billingData = [
                'first_name' => $request->billing_first_name,
                'last_name' => $request->billing_last_name,
                'email' => $request->billing_email,
                'phone' => $request->billing_phone,
                'address' => $request->billing_address,
                'city' => $request->billing_city,
                'state' => $request->billing_state,
                'postal_code' => $request->billing_postal_code,
                'country' => $request->billing_country,
            ];

            $order = Order::createFromCart($cart, $shippingData, $billingData);

            // Store customer notes if provided
            if ($request->notes) {
                $order->update(['notes' => $request->notes]);
            }

            // Create payment record
            $paymentStatus = 'pending';
            $orderStatus = 'pending';
            
            if ($request->payment_method === 'bank_transfer') {
                $paymentStatus = 'pending'; // Waiting for slip upload
                $orderStatus = 'pending';
            } elseif ($request->payment_method === 'cod') {
                $paymentStatus = 'pending';
                $orderStatus = 'processing';
            }

            $payment = Payment::create([
                'order_id' => $order->id,
                'payment_method' => $request->payment_method,
                'amount' => $order->total,
                'status' => $paymentStatus,
                'bank_reference' => $request->bank_reference,
            ]);

            $order->update(['status' => $orderStatus]);

            // Increment coupon usage if used
            if ($cart->coupon_code) {
                $coupon = \App\Models\Coupon::where('code', $cart->coupon_code)->first();
                $coupon?->incrementUsage();
            }

            // Clear cart
            $cart->clear();

            DB::commit();

            $order->load([
                'items.product.primaryImage',
                'items.variant',
                'items.album',
                'payments'
            ]);

            return response()->json([
                'message' => 'Order placed successfully',
                'order' => $order,
                'payment' => $payment,
                'requires_slip_upload' => $request->payment_method === 'bank_transfer',
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'message' => 'Failed to create order',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cancel order
     */
    public function cancel(Request $request, string $orderNumber)
    {
        $order = Order::where('order_number', $orderNumber)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        if (!in_array($order->status, ['pending', 'processing'])) {
            return response()->json([
                'message' => 'Order cannot be cancelled at this stage'
            ], 400);
        }

        $order->updateStatus('cancelled');

        return response()->json([
            'message' => 'Order cancelled successfully',
            'order' => $order
        ]);
    }
}
