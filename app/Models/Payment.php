<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Payment Model
 * Represents payment transactions
 */
class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'transaction_id',
        'payment_method',
        'amount',
        'status',
        'gateway',
        'gateway_response',
        'payment_slip',
        'bank_reference',
        'slip_uploaded_at',
        'verified_at',
        'verified_by',
        'notes',
        'paid_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'gateway_response' => 'array',
        'paid_at' => 'datetime',
        'slip_uploaded_at' => 'datetime',
        'verified_at' => 'datetime',
    ];

    /**
     * Get the order this payment belongs to
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get the admin who verified the payment
     */
    public function verifier()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /**
     * Mark payment as completed
     */
    public function markAsCompleted(string $transactionId = null)
    {
        $this->status = 'completed';
        $this->paid_at = now();
        
        if ($transactionId) {
            $this->transaction_id = $transactionId;
        }
        
        $this->save();
        
        // Update order payment status
        $this->order->update(['payment_status' => 'paid']);
    }

    /**
     * Mark payment as failed
     */
    public function markAsFailed()
    {
        $this->status = 'failed';
        $this->save();
        
        $this->order->update(['payment_status' => 'failed']);
    }

    /**
     * Verify bank transfer payment
     */
    public function verifyBankTransfer(int $adminId)
    {
        $this->status = 'completed';
        $this->verified_at = now();
        $this->verified_by = $adminId;
        $this->paid_at = now();
        $this->save();
        
        // Update order status
        $this->order->update([
            'payment_status' => 'paid',
            'status' => 'processing'
        ]);
    }

    /**
     * Reject bank transfer payment
     */
    public function rejectBankTransfer(int $adminId, string $reason = null)
    {
        $this->status = 'failed';
        $this->verified_at = now();
        $this->verified_by = $adminId;
        $this->notes = $reason;
        $this->save();
        
        $this->order->update(['payment_status' => 'failed']);
    }

    /**
     * Check if payment slip exists
     */
    public function hasPaymentSlip(): bool
    {
        return !empty($this->payment_slip);
    }

    /**
     * Check if payment is pending verification
     */
    public function isPendingVerification(): bool
    {
        return $this->payment_method === 'bank_transfer' 
            && $this->hasPaymentSlip() 
            && $this->status === 'processing';
    }

    /**
     * Scope for completed payments
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope for pending payments
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for bank transfers pending verification
     */
    public function scopePendingVerification($query)
    {
        return $query->where('payment_method', 'bank_transfer')
            ->whereNotNull('payment_slip')
            ->where('status', 'processing');
    }
}
