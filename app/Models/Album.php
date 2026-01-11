<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Album Model
 * Represents collections of products that can be purchased together
 */
class Album extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'cover_image',
        'price',
        'discount_percentage',
        'is_featured',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'discount_percentage' => 'decimal:2',
        'is_featured' => 'boolean',
        'is_active' => 'boolean',
    ];

    protected $appends = ['calculated_price', 'final_price'];

    /**
     * Boot method to auto-generate slug
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($album) {
            if (empty($album->slug)) {
                $album->slug = Str::slug($album->name);
            }
        });
    }

    /**
     * Get products in this album
     */
    public function products()
    {
        return $this->belongsToMany(Product::class, 'album_product')
            ->withPivot('variant_id', 'quantity', 'sort_order')
            ->withTimestamps()
            ->orderBy('album_product.sort_order');
    }

    /**
     * Get calculated price (sum of all products)
     */
    public function getCalculatedPriceAttribute()
    {
        return $this->products->sum(function ($product) {
            $quantity = $product->pivot->quantity ?? 1;
            return $product->final_price * $quantity;
        });
    }

    /**
     * Get final price with discount
     */
    public function getFinalPriceAttribute()
    {
        $basePrice = $this->price ?? $this->calculated_price;
        
        if ($this->discount_percentage > 0) {
            return $basePrice * (1 - ($this->discount_percentage / 100));
        }
        
        return $basePrice;
    }

    /**
     * Get savings amount
     */
    public function getSavingsAttribute()
    {
        return max(0, $this->calculated_price - $this->final_price);
    }

    /**
     * Check if all products in album are in stock
     */
    public function isInStock(): bool
    {
        foreach ($this->products as $product) {
            $quantity = $product->pivot->quantity ?? 1;
            $variantId = $product->pivot->variant_id;
            
            if ($variantId) {
                $inventory = Inventory::where('product_id', $product->id)
                    ->where('variant_id', $variantId)
                    ->first();
            } else {
                $inventory = Inventory::where('product_id', $product->id)
                    ->whereNull('variant_id')
                    ->first();
            }
            
            if (!$inventory || $inventory->available_quantity < $quantity) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Scope for active albums
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for featured albums
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }
}
