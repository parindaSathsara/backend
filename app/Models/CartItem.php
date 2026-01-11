<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * CartItem Model
 * Represents individual items in cart (products or albums)
 */
class CartItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'cart_id',
        'product_id',
        'variant_id',
        'album_id',
        'item_type',
        'quantity',
        'price',
        'subtotal',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'price' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    /**
     * Get the cart this item belongs to
     */
    public function cart()
    {
        return $this->belongsTo(Cart::class);
    }

    /**
     * Get the product (if item is product)
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the variant (if applicable)
     */
    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }

    /**
     * Get the album (if item is album)
     */
    public function album()
    {
        return $this->belongsTo(Album::class);
    }

    /**
     * Update quantity
     */
    public function updateQuantity(int $quantity)
    {
        $this->quantity = $quantity;
        $this->subtotal = $this->price * $this->quantity;
        $this->save();
        
        $this->cart->calculateTotals();
    }

    /**
     * Get item name
     */
    public function getNameAttribute()
    {
        if ($this->item_type === 'album') {
            return $this->album->name;
        }
        
        $name = $this->product->name;
        
        if ($this->variant) {
            $name .= ' - ' . $this->variant->variant_name;
        }
        
        return $name;
    }

    /**
     * Check if item is in stock
     */
    public function isInStock(): bool
    {
        if ($this->item_type === 'album') {
            return $this->album->isInStock();
        }
        
        if ($this->variant_id) {
            $inventory = Inventory::where('product_id', $this->product_id)
                ->where('variant_id', $this->variant_id)
                ->first();
        } else {
            $inventory = Inventory::where('product_id', $this->product_id)
                ->whereNull('variant_id')
                ->first();
        }
        
        return $inventory && $inventory->available_quantity >= $this->quantity;
    }
}
