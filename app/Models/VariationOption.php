<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * VariationOption Model
 * Stores dynamic variation option values (Red, Blue, Small, Large, Cotton, etc.)
 * Linked to VariationType for full flexibility
 */
class VariationOption extends Model
{
    use HasFactory;

    protected $fillable = [
        'variation_type_id',
        'name',
        'value',
        'display_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'display_order' => 'integer',
    ];

    /**
     * Get the variation type this option belongs to
     */
    public function variationType()
    {
        return $this->belongsTo(VariationType::class);
    }

    /**
     * Get product variants using this option
     */
    public function productVariants()
    {
        return $this->belongsToMany(ProductVariant::class, 'product_variant_options')
            ->withTimestamps();
    }

    /**
     * Scope to filter active options
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to filter by variation type
     */
    public function scopeOfType($query, $typeSlug)
    {
        return $query->whereHas('variationType', function ($q) use ($typeSlug) {
            $q->where('slug', $typeSlug);
        });
    }
}
