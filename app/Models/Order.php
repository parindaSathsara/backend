<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Order Model
 * Represents customer orders
 */
class Order extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'order_number',
        'user_id',
        'subtotal',
        'tax',
        'shipping',
        'discount',
        'total',
        'status',
        'payment_status',
        'shipping_first_name',
        'shipping_last_name',
        'shipping_email',
        'shipping_phone',
        'shipping_address',
        'shipping_city',
        'shipping_state',
        'shipping_postal_code',
        'shipping_country',
        'billing_first_name',
        'billing_last_name',
        'billing_email',
        'billing_phone',
        'billing_address',
        'billing_city',
        'billing_state',
        'billing_postal_code',
        'billing_country',
        'notes',
        'coupon_code',
        'tracking_number',
        'shipped_at',
        'delivered_at',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'tax' => 'decimal:2',
        'shipping' => 'decimal:2',
        'discount' => 'decimal:2',
        'total' => 'decimal:2',
        'shipped_at' => 'datetime',
        'delivered_at' => 'datetime',
    ];

    /**
     * Boot method to auto-generate order number
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($order) {
            if (empty($order->order_number)) {
                $order->order_number = 'ORD-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
            }
        });
    }

    /**
     * Get the user who placed the order
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get order items
     */
    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Get payment for this order
     */
    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Get successful payment
     */
    public function successfulPayment()
    {
        return $this->hasOne(Payment::class)->where('status', 'completed');
    }

    /**
     * Create order from cart
     */
    public static function createFromCart(Cart $cart, array $shippingData, array $billingData)
    {
        $order = static::create([
            'user_id' => $cart->user_id,
            'subtotal' => $cart->subtotal,
            'tax' => $cart->tax,
            'shipping' => $cart->shipping,
            'discount' => $cart->discount,
            'total' => $cart->total,
            'coupon_code' => $cart->coupon_code,
            'shipping_first_name' => $shippingData['first_name'],
            'shipping_last_name' => $shippingData['last_name'],
            'shipping_email' => $shippingData['email'],
            'shipping_phone' => $shippingData['phone'],
            'shipping_address' => $shippingData['address'],
            'shipping_city' => $shippingData['city'],
            'shipping_state' => $shippingData['state'],
            'shipping_postal_code' => $shippingData['postal_code'],
            'shipping_country' => $shippingData['country'] ?? 'Sri Lanka',
            'billing_first_name' => $billingData['first_name'],
            'billing_last_name' => $billingData['last_name'],
            'billing_email' => $billingData['email'],
            'billing_phone' => $billingData['phone'],
            'billing_address' => $billingData['address'],
            'billing_city' => $billingData['city'],
            'billing_state' => $billingData['state'],
            'billing_postal_code' => $billingData['postal_code'],
            'billing_country' => $billingData['country'] ?? 'Sri Lanka',
        ]);

        // Copy cart items to order items
        foreach ($cart->items as $cartItem) {
            $metaData = [];
            
            if ($cartItem->item_type === 'product') {
                $metaData = [
                    'variant' => $cartItem->variant?->variant_name,
                    'color' => $cartItem->variant?->color,
                    'size' => $cartItem->variant?->size,
                    'material' => $cartItem->variant?->material,
                ];
            }

            $order->items()->create([
                'product_id' => $cartItem->product_id,
                'variant_id' => $cartItem->variant_id,
                'album_id' => $cartItem->album_id,
                'item_type' => $cartItem->item_type,
                'name' => $cartItem->name,
                'sku' => $cartItem->product?->sku ?? $cartItem->album?->slug,
                'quantity' => $cartItem->quantity,
                'price' => $cartItem->price,
                'subtotal' => $cartItem->subtotal,
                'meta_data' => $metaData,
            ]);

            // Reserve inventory
            if ($cartItem->item_type === 'product') {
                $inventory = Inventory::where('product_id', $cartItem->product_id)
                    ->where('variant_id', $cartItem->variant_id)
                    ->first();
                
                $inventory?->reserve($cartItem->quantity);
            }
        }

        return $order;
    }

    /**
     * Update order status
     */
    public function updateStatus(string $status)
    {
        $this->status = $status;
        
        if ($status === 'shipped') {
            $this->shipped_at = now();
        } elseif ($status === 'delivered') {
            $this->delivered_at = now();
            
            // Deduct inventory after delivery
            foreach ($this->items as $item) {
                if ($item->item_type === 'product') {
                    $inventory = Inventory::where('product_id', $item->product_id)
                        ->where('variant_id', $item->variant_id)
                        ->first();
                    
                    $inventory?->deduct($item->quantity);
                }
            }
        } elseif ($status === 'cancelled') {
            // Release reserved inventory
            foreach ($this->items as $item) {
                if ($item->item_type === 'product') {
                    $inventory = Inventory::where('product_id', $item->product_id)
                        ->where('variant_id', $item->variant_id)
                        ->first();
                    
                    $inventory?->release($item->quantity);
                }
            }
        }
        
        $this->save();
    }

    /**
     * Scope for pending orders
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for recent orders
     */
    public function scopeRecent($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    /**
     * Get shipping full name
     */
    public function getShippingFullNameAttribute()
    {
        return "{$this->shipping_first_name} {$this->shipping_last_name}";
    }

    /**
     * Get billing full name
     */
    public function getBillingFullNameAttribute()
    {
        return "{$this->billing_first_name} {$this->billing_last_name}";
    }
}
