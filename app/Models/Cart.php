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
        
        // Calculate tax (example: 18% GST)
        $this->tax = $this->subtotal * 0.18;
        
        // Calculate shipping (example: free above 1000)
        $this->shipping = $this->subtotal >= 1000 ? 0 : 50;
        
        // Apply coupon discount if exists
        $this->discount = $this->calculateDiscount();
        
        // Calculate total
        $this->total = $this->subtotal + $this->tax + $this->shipping - $this->discount;
        
        $this->save();
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
