<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * Product Model
 * Represents products (Sarees, Shirts, Jewelry, etc.)
 */
class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'category_id',
        'name',
        'slug',
        'sku',
        'description',
        'short_description',
        'price',
        'sale_price',
        'cost_price',
        'is_featured',
        'is_trending',
        'is_active',
        'sort_order',
        'meta_data',
        'meta_title',
        'meta_description',
        'meta_keywords',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'is_featured' => 'boolean',
        'is_trending' => 'boolean',
        'is_active' => 'boolean',
        'meta_data' => 'array',
    ];

    protected $appends = ['final_price', 'discount_percentage'];

    /**
     * Boot method to auto-generate slug and SKU
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($product) {
            if (empty($product->slug)) {
                $product->slug = Str::slug($product->name);
            }
            if (empty($product->sku)) {
                $product->sku = 'PRD-' . strtoupper(Str::random(8));
            }
        });
    }

    /**
     * Get the category this product belongs to
     */
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get product variants
     */
    public function variants()
    {
        return $this->hasMany(ProductVariant::class);
    }

    /**
     * Get product images
     */
    public function images()
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    /**
     * Get primary image
     */
    public function primaryImage()
    {
        return $this->hasOne(ProductImage::class)->where('is_primary', true);
    }

    /**
     * Get all inventory records for this product (including variants)
     */
    public function inventories()
    {
        return $this->hasMany(Inventory::class);
    }

    /**
     * Get base product inventory (without variant)
     */
    public function inventory()
    {
        return $this->hasOne(Inventory::class)->whereNull('variant_id');
    }

    /**
     * Get reviews for this product
     */
    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    /**
     * Get approved reviews
     */
    public function approvedReviews()
    {
        return $this->reviews()->where('is_approved', true);
    }

    /**
     * Get albums containing this product
     */
    public function albums()
    {
        return $this->belongsToMany(Album::class, 'album_product')
            ->withPivot('variant_id', 'quantity', 'sort_order')
            ->withTimestamps();
    }

    /**
     * Get users who wishlisted this product
     */
    public function wishlistedBy()
    {
        return $this->belongsToMany(User::class, 'wishlists')
            ->withTimestamps();
    }

    /**
     * Get final price (considering sale price)
     */
    public function getFinalPriceAttribute()
    {
        return $this->sale_price ?? $this->price;
    }

    /**
     * Get discount percentage
     */
    public function getDiscountPercentageAttribute()
    {
        if ($this->sale_price && $this->price > $this->sale_price) {
            return round((($this->price - $this->sale_price) / $this->price) * 100, 2);
        }
        return 0;
    }

    /**
     * Get average rating
     */
    public function getAverageRatingAttribute()
    {
        return $this->approvedReviews()->avg('rating') ?? 0;
    }

    /**
     * Get total stock across all variants
     */
    public function getTotalStockAttribute()
    {
        return $this->inventories()->sum('quantity');
    }

    /**
     * Check if product is in stock
     */
    public function isInStock(): bool
    {
        return $this->total_stock > 0;
    }

    /**
     * Scope for active products
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for featured products
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope for trending products
     */
    public function scopeTrending($query)
    {
        return $query->where('is_trending', true);
    }

    /**
     * Scope for products on sale
     */
    public function scopeOnSale($query)
    {
        return $query->whereNotNull('sale_price');
    }
}
