<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Coupon Controller
 * Handles coupon validation
 */
class CouponController extends Controller
{
    /**
     * Validate a coupon
     */
    public function validate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|string',
            'purchase_amount' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $coupon = Coupon::where('code', $request->code)->first();

        if (!$coupon) {
            return response()->json([
                'message' => 'Invalid coupon code',
                'valid' => false
            ], 404);
        }

        $isValid = $coupon->isValid($request->purchase_amount, $request->user());

        if (!$isValid) {
            return response()->json([
                'message' => 'Coupon is not valid or has expired',
                'valid' => false
            ], 400);
        }

        $discount = $coupon->calculateDiscount($request->purchase_amount);

        return response()->json([
            'message' => 'Coupon is valid',
            'valid' => true,
            'coupon' => $coupon,
            'discount_amount' => $discount
        ]);
    }
}
