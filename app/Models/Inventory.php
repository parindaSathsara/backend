<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Inventory Model
 * Tracks stock levels for products and variants
 */
class Inventory extends Model
{
    use HasFactory;

    protected $table = 'inventory';

    protected $fillable = [
        'product_id',
        'variant_id',
        'quantity',
        'reserved_quantity',
        'low_stock_threshold',
        'track_inventory',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'reserved_quantity' => 'integer',
        'low_stock_threshold' => 'integer',
        'track_inventory' => 'boolean',
    ];

    protected $appends = ['available_quantity', 'is_low_stock'];

    /**
     * Get the product
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the variant
     */
    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }

    /**
     * Get available quantity (total - reserved)
     */
    public function getAvailableQuantityAttribute()
    {
        return max(0, $this->quantity - $this->reserved_quantity);
    }

    /**
     * Check if stock is low
     */
    public function getIsLowStockAttribute()
    {
        return $this->track_inventory && $this->available_quantity <= $this->low_stock_threshold;
    }

    /**
     * Check if item is in stock
     */
    public function isInStock(): bool
    {
        return !$this->track_inventory || $this->available_quantity > 0;
    }

    /**
     * Reserve quantity for order
     */
    public function reserve(int $quantity): bool
    {
        if ($this->available_quantity >= $quantity) {
            $this->reserved_quantity += $quantity;
            return $this->save();
        }
        return false;
    }

    /**
     * Release reserved quantity
     */
    public function release(int $quantity): bool
    {
        $this->reserved_quantity = max(0, $this->reserved_quantity - $quantity);
        return $this->save();
    }

    /**
     * Deduct quantity after order confirmation
     */
    public function deduct(int $quantity): bool
    {
        if ($this->quantity >= $quantity) {
            $this->quantity -= $quantity;
            $this->reserved_quantity = max(0, $this->reserved_quantity - $quantity);
            return $this->save();
        }
        return false;
    }

    /**
     * Scope for low stock items
     */
    public function scopeLowStock($query)
    {
        return $query->whereRaw('(quantity - reserved_quantity) <= low_stock_threshold')
            ->where('track_inventory', true);
    }

    /**
     * Scope for out of stock items
     */
    public function scopeOutOfStock($query)
    {
        return $query->whereRaw('(quantity - reserved_quantity) <= 0')
            ->where('track_inventory', true);
    }
}
