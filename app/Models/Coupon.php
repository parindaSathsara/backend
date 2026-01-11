<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Coupon Model
 * Represents discount coupons and promotional codes
 */
class Coupon extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'description',
        'type',
        'value',
        'min_purchase_amount',
        'max_discount_amount',
        'usage_limit',
        'usage_per_user',
        'used_count',
        'is_active',
        'start_date',
        'end_date',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'min_purchase_amount' => 'decimal:2',
        'max_discount_amount' => 'decimal:2',
        'usage_limit' => 'integer',
        'usage_per_user' => 'integer',
        'used_count' => 'integer',
        'is_active' => 'boolean',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
    ];

    /**
     * Check if coupon is valid
     */
    public function isValid(float $purchaseAmount = 0, ?User $user = null): bool
    {
        // Check if active
        if (!$this->is_active) {
            return false;
        }

        // Check date range
        if ($this->start_date && now()->lt($this->start_date)) {
            return false;
        }

        if ($this->end_date && now()->gt($this->end_date)) {
            return false;
        }

        // Check minimum purchase amount
        if ($purchaseAmount < $this->min_purchase_amount) {
            return false;
        }

        // Check total usage limit
        if ($this->usage_limit && $this->used_count >= $this->usage_limit) {
            return false;
        }

        // Check per-user usage limit
        if ($user) {
            $userUsage = Order::where('user_id', $user->id)
                ->where('coupon_code', $this->code)
                ->count();

            if ($userUsage >= $this->usage_per_user) {
                return false;
            }
        }

        return true;
    }

    /**
     * Calculate discount amount
     */
    public function calculateDiscount(float $purchaseAmount): float
    {
        if (!$this->isValid($purchaseAmount)) {
            return 0;
        }

        if ($this->type === 'percentage') {
            $discount = $purchaseAmount * ($this->value / 100);

            if ($this->max_discount_amount) {
                $discount = min($discount, $this->max_discount_amount);
            }

            return $discount;
        }

        // Fixed amount
        return min($this->value, $purchaseAmount);
    }

    /**
     * Increment usage count
     */
    public function incrementUsage()
    {
        $this->increment('used_count');
    }

    /**
     * Scope for active coupons
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('start_date')
                    ->orWhere('start_date', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('end_date')
                    ->orWhere('end_date', '>=', now());
            });
    }
}
