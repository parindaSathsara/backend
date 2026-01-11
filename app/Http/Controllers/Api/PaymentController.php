<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * Payment Controller
 * Handles payment slip uploads and verification
 */
class PaymentController extends Controller
{
    /**
     * Get bank details for transfer
     */
    public function getBankDetails()
    {
        // These should ideally come from a settings table
        return response()->json([
            'bank_details' => [
                'bank_name' => 'Bank of Ceylon',
                'account_number' => '0012345678901',
                'account_name' => 'SH Womens Fashion (Pvt) Ltd',
                'branch' => 'Colombo Main Branch',
                'branch_code' => '001',
                'swift_code' => 'BABORLKX',
            ],
            'instructions' => [
                'Make the transfer to the above account',
                'Include your Order Number as the payment reference',
                'Upload the payment slip/receipt after transfer',
                'Your order will be processed once payment is verified',
            ],
        ]);
    }

    /**
     * Upload payment slip for bank transfer
     */
    public function uploadSlip(Request $request, string $orderNumber)
    {
        $validator = Validator::make($request->all(), [
            'payment_slip' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120', // 5MB max
            'bank_reference' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Find the order
        $order = Order::where('order_number', $orderNumber)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        // Get the payment
        $payment = $order->payments()
            ->where('payment_method', 'bank_transfer')
            ->first();

        if (!$payment) {
            return response()->json([
                'message' => 'No bank transfer payment found for this order'
            ], 400);
        }

        // Check if slip already uploaded and verified
        if ($payment->status === 'completed') {
            return response()->json([
                'message' => 'Payment has already been verified'
            ], 400);
        }

        // Store the payment slip securely
        $file = $request->file('payment_slip');
        $filename = 'payment_slips/' . $order->order_number . '_' . Str::random(10) . '.' . $file->getClientOriginalExtension();
        
        // Store in private storage (not publicly accessible)
        $path = $file->storeAs('private', $filename);

        // Delete old slip if exists
        if ($payment->payment_slip) {
            Storage::delete('private/' . $payment->payment_slip);
        }

        // Update payment record
        $payment->update([
            'payment_slip' => $filename,
            'bank_reference' => $request->bank_reference ?? $payment->bank_reference,
            'slip_uploaded_at' => now(),
            'status' => 'processing', // Mark as processing for admin review
        ]);

        return response()->json([
            'message' => 'Payment slip uploaded successfully',
            'payment' => $payment,
        ]);
    }

    /**
     * Get payment status for an order
     */
    public function getPaymentStatus(Request $request, string $orderNumber)
    {
        $order = Order::where('order_number', $orderNumber)
            ->where('user_id', $request->user()->id)
            ->with('payments')
            ->firstOrFail();

        $payment = $order->payments->first();

        return response()->json([
            'order_number' => $order->order_number,
            'payment_method' => $payment?->payment_method,
            'payment_status' => $payment?->status,
            'slip_uploaded' => $payment?->hasPaymentSlip(),
            'slip_uploaded_at' => $payment?->slip_uploaded_at,
            'verified_at' => $payment?->verified_at,
        ]);
    }
}
