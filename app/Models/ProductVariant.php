<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * ProductVariant Model
 * Represents product variations with dynamic attributes
 */
class ProductVariant extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'sku',
        'variant_name',
        'price_adjustment',
        'image',
        'is_active',
    ];

    protected $casts = [
        'price_adjustment' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    protected $appends = ['final_price', 'variation_attributes'];

    /**
     * Get the product this variant belongs to
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get images for this variant
     */
    public function images()
    {
        return $this->hasMany(ProductImage::class, 'variant_id');
    }

    /**
     * Get inventory for this variant
     */
    public function inventory()
    {
        return $this->hasOne(Inventory::class, 'variant_id');
    }

    /**
     * Get variation options for this variant
     */
    public function variationOptions()
    {
        return $this->belongsToMany(VariationOption::class, 'product_variant_options')
            ->withPivot('variation_type_id')
            ->with('variationType')
            ->withTimestamps();
    }

    /**
     * Get final price including adjustment
     */
    public function getFinalPriceAttribute()
    {
        $basePrice = $this->product->final_price;
        return $basePrice + $this->price_adjustment;
    }

    /**
     * Get variation attributes as key-value pairs
     */
    public function getVariationAttributesAttribute()
    {
        $attributes = [];
        foreach ($this->variationOptions as $option) {
            $typeSlug = $option->variationType->slug;
            $attributes[$typeSlug] = [
                'type_name' => $option->variationType->name,
                'value_name' => $option->name,
                'value_data' => $option->value,
            ];
        }
        return $attributes;
    }

    /**
     * Check if variant is in stock
     */
    public function isInStock(): bool
    {
        if (!$this->inventory) {
            return false;
        }
        return $this->inventory->available_quantity > 0;
    }

    /**
     * Get available quantity
     */
    public function getAvailableQuantityAttribute()
    {
        return $this->inventory ? $this->inventory->available_quantity : 0;
    }

    /**
     * Scope for active variants
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Set variation option for a specific type
     */
    public function setVariationOption($variationTypeId, $variationOptionId)
    {
        // Remove existing option for this variation type
        \DB::table('product_variant_options')
            ->where('product_variant_id', $this->id)
            ->where('variation_type_id', $variationTypeId)
            ->delete();

        // Add new option
        \DB::table('product_variant_options')->insert([
            'product_variant_id' => $this->id,
            'variation_type_id' => $variationTypeId,
            'variation_option_id' => $variationOptionId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
