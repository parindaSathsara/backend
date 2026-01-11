<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

/**
 * Admin Payment Controller
 * Handles payment verification and management
 */
class AdminPaymentController extends Controller
{
    /**
     * Get all payments pending verification
     */
    public function pendingVerification(Request $request)
    {
        $payments = Payment::with(['order.user', 'order.items'])
            ->pendingVerification()
            ->orderBy('slip_uploaded_at', 'asc')
            ->paginate(20);

        return response()->json($payments);
    }

    /**
     * Get all payments
     */
    public function index(Request $request)
    {
        $query = Payment::with(['order.user', 'verifier']);

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by payment method
        if ($request->has('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }

        // Filter by date range
        if ($request->has('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }
        if ($request->has('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        $payments = $query->orderBy('created_at', 'desc')->paginate(20);

        return response()->json($payments);
    }

    /**
     * Get single payment details
     */
    public function show(Payment $payment)
    {
        $payment->load(['order.user', 'order.items.product', 'verifier']);

        return response()->json([
            'payment' => $payment,
        ]);
    }

    /**
     * Download/view payment slip
     */
    public function viewSlip(Payment $payment)
    {
        if (!$payment->payment_slip) {
            return response()->json([
                'message' => 'No payment slip found'
            ], 404);
        }

        $path = 'private/' . $payment->payment_slip;

        if (!Storage::exists($path)) {
            return response()->json([
                'message' => 'Payment slip file not found'
            ], 404);
        }

        return Storage::download($path, 'payment_slip_' . $payment->order->order_number . '.' . pathinfo($payment->payment_slip, PATHINFO_EXTENSION));
    }

    /**
     * Verify bank transfer payment
     */
    public function verify(Request $request, Payment $payment)
    {
        if ($payment->status === 'completed') {
            return response()->json([
                'message' => 'Payment has already been verified'
            ], 400);
        }

        if ($payment->payment_method !== 'bank_transfer') {
            return response()->json([
                'message' => 'Only bank transfer payments can be verified this way'
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'transaction_id' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $payment->verifyBankTransfer($request->user()->id);

        if ($request->transaction_id) {
            $payment->update(['transaction_id' => $request->transaction_id]);
        }
        if ($request->notes) {
            $payment->update(['notes' => $request->notes]);
        }

        $payment->load(['order', 'verifier']);

        return response()->json([
            'message' => 'Payment verified successfully',
            'payment' => $payment,
        ]);
    }

    /**
     * Reject bank transfer payment
     */
    public function reject(Request $request, Payment $payment)
    {
        if ($payment->status === 'completed') {
            return response()->json([
                'message' => 'Cannot reject a completed payment'
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'reason' => 'required|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $payment->rejectBankTransfer($request->user()->id, $request->reason);

        $payment->load(['order', 'verifier']);

        return response()->json([
            'message' => 'Payment rejected',
            'payment' => $payment,
        ]);
    }

    /**
     * Get payment statistics
     */
    public function stats()
    {
        $stats = [
            'pending_verification' => Payment::pendingVerification()->count(),
            'total_pending' => Payment::pending()->count(),
            'total_completed' => Payment::completed()->count(),
            'bank_transfers_today' => Payment::where('payment_method', 'bank_transfer')
                ->whereDate('created_at', today())
                ->count(),
            'total_revenue_today' => Payment::completed()
                ->whereDate('paid_at', today())
                ->sum('amount'),
            'total_revenue_month' => Payment::completed()
                ->whereMonth('paid_at', now()->month)
                ->whereYear('paid_at', now()->year)
                ->sum('amount'),
        ];

        return response()->json($stats);
    }
}
