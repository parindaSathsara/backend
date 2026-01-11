<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * VariationType Model
 * Defines types of variations (Color, Size, Material, Gold Type, etc.)
 * Admin can add new variation types dynamically
 */
class VariationType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'input_type',
        'is_required',
        'is_active',
        'display_order',
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'is_active' => 'boolean',
        'display_order' => 'integer',
    ];

    /**
     * Get all variation options for this type
     */
    public function options()
    {
        return $this->hasMany(VariationOption::class)->orderBy('display_order');
    }

    /**
     * Get active options only
     */
    public function activeOptions()
    {
        return $this->options()->where('is_active', true);
    }

    /**
     * Scope to filter active types
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('display_order');
    }

    /**
     * Get type by slug
     */
    public static function findBySlug($slug)
    {
        return static::where('slug', $slug)->first();
    }
}
