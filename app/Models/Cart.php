<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Cart Model
 * Represents shopping cart for users or guest sessions
 */
class Cart extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'session_id',
        'subtotal',
        'tax',
        'shipping',
        'discount',
        'total',
        'coupon_code',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'tax' => 'decimal:2',
        'shipping' => 'decimal:2',
        'discount' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    /**
     * Get the user who owns the cart
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get cart items
     */
    public function items()
    {
        return $this->hasMany(CartItem::class);
    }

    /**
     * Calculate cart totals
     */
    public function calculateTotals()
    {
        $this->subtotal = $this->items->sum('subtotal');
        
        // No tax - prices are all-inclusive
        $this->tax = 0;
        
        // Calculate shipping based on weight
        $this->shipping = $this->calculateShipping();
        
        // Apply coupon discount if exists
        $this->discount = $this->calculateDiscount();
        
        // Calculate total (subtotal minus discount plus shipping)
        $this->total = $this->subtotal - $this->discount + $this->shipping;
        
        $this->save();
    }

    /**
     * Calculate shipping based on total weight of items
     */
    public function calculateShipping()
    {
        $shippingRatePerKg = Setting::get('shipping_rate_per_kg', 500);
        $freeShippingThreshold = Setting::get('free_shipping_threshold', 0);
        $defaultWeight = Setting::get('default_weight', 0.5);
        
        // Check if free shipping applies
        if ($freeShippingThreshold > 0 && $this->subtotal >= $freeShippingThreshold) {
            return 0;
        }
        
        // Calculate total weight
        $totalWeight = 0;
        foreach ($this->items as $item) {
            $itemWeight = $defaultWeight; // Default weight
            
            if ($item->item_type === 'product' && $item->product) {
                $itemWeight = $item->product->weight ?? $defaultWeight;
            } elseif ($item->item_type === 'album' && $item->album) {
                // For albums, calculate weight from all products in the album
                $albumWeight = 0;
                foreach ($item->album->products as $product) {
                    $albumWeight += ($product->weight ?? $defaultWeight);
                }
                $itemWeight = $albumWeight > 0 ? $albumWeight : $defaultWeight;
            }
            
            $totalWeight += $itemWeight * $item->quantity;
        }
        
        // Calculate shipping cost (minimum 1 kg)
        $chargeableWeight = max($totalWeight, 0.1);
        $shippingCost = ceil($chargeableWeight) * $shippingRatePerKg;
        
        return $shippingCost;
    }

    /**
     * Get shipping calculation breakdown
     */
    public function getShippingBreakdown()
    {
        $shippingRatePerKg = Setting::get('shipping_rate_per_kg', 500);
        $freeShippingThreshold = Setting::get('free_shipping_threshold', 0);
        $defaultWeight = Setting::get('default_weight', 0.5);
        
        $totalWeight = 0;
        $itemWeights = [];
        
        foreach ($this->items as $item) {
            $itemWeight = $defaultWeight;
            
            if ($item->item_type === 'product' && $item->product) {
                $itemWeight = $item->product->weight ?? $defaultWeight;
            } elseif ($item->item_type === 'album' && $item->album) {
                $albumWeight = 0;
                foreach ($item->album->products as $product) {
                    $albumWeight += ($product->weight ?? $defaultWeight);
                }
                $itemWeight = $albumWeight > 0 ? $albumWeight : $defaultWeight;
            }
            
            $lineWeight = $itemWeight * $item->quantity;
            $totalWeight += $lineWeight;
            
            $itemWeights[] = [
                'name' => $item->name,
                'unit_weight' => $itemWeight,
                'quantity' => $item->quantity,
                'total_weight' => $lineWeight,
            ];
        }
        
        $isFreeShipping = $freeShippingThreshold > 0 && $this->subtotal >= $freeShippingThreshold;
        $chargeableWeight = max($totalWeight, 0.1);
        
        return [
            'items' => $itemWeights,
            'total_weight' => $totalWeight,
            'chargeable_weight' => ceil($chargeableWeight),
            'rate_per_kg' => $shippingRatePerKg,
            'free_shipping_threshold' => $freeShippingThreshold,
            'is_free_shipping' => $isFreeShipping,
            'shipping_cost' => $isFreeShipping ? 0 : ceil($chargeableWeight) * $shippingRatePerKg,
        ];
    }

    /**
     * Calculate discount from coupon
     */
    protected function calculateDiscount()
    {
        if (!$this->coupon_code) {
            return 0;
        }
        
        $coupon = Coupon::where('code', $this->coupon_code)
            ->where('is_active', true)
            ->first();
        
        if (!$coupon || !$coupon->isValid($this->subtotal)) {
            return 0;
        }
        
        if ($coupon->type === 'percentage') {
            $discount = $this->subtotal * ($coupon->value / 100);
            
            if ($coupon->max_discount_amount) {
                $discount = min($discount, $coupon->max_discount_amount);
            }
            
            return $discount;
        }
        
        // Fixed amount
        return min($coupon->value, $this->subtotal);
    }

    /**
     * Add product to cart
     */
    public function addProduct(Product $product, ?ProductVariant $variant = null, int $quantity = 1)
    {
        $existingItem = $this->items()
            ->where('product_id', $product->id)
            ->where('variant_id', $variant?->id)
            ->where('item_type', 'product')
            ->first();
        
        if ($existingItem) {
            $existingItem->quantity += $quantity;
            $existingItem->subtotal = $existingItem->price * $existingItem->quantity;
            $existingItem->save();
        } else {
            $price = $variant ? $variant->final_price : $product->final_price;
            
            $this->items()->create([
                'product_id' => $product->id,
                'variant_id' => $variant?->id,
                'item_type' => 'product',
                'quantity' => $quantity,
                'price' => $price,
                'subtotal' => $price * $quantity,
            ]);
        }
        
        $this->calculateTotals();
    }

    /**
     * Add album to cart
     */
    public function addAlbum(Album $album, int $quantity = 1)
    {
        $existingItem = $this->items()
            ->where('album_id', $album->id)
            ->where('item_type', 'album')
            ->first();
        
        if ($existingItem) {
            $existingItem->quantity += $quantity;
            $existingItem->subtotal = $existingItem->price * $existingItem->quantity;
            $existingItem->save();
        } else {
            $this->items()->create([
                'album_id' => $album->id,
                'item_type' => 'album',
                'quantity' => $quantity,
                'price' => $album->final_price,
                'subtotal' => $album->final_price * $quantity,
            ]);
        }
        
        $this->calculateTotals();
    }

    /**
     * Remove item from cart
     */
    public function removeItem(CartItem $item)
    {
        $item->delete();
        $this->calculateTotals();
    }

    /**
     * Clear all items from cart
     */
    public function clear()
    {
        $this->items()->delete();
        $this->calculateTotals();
    }

    /**
     * Get item count
     */
    public function getItemCountAttribute()
    {
        return $this->items->sum('quantity');
    }
}
