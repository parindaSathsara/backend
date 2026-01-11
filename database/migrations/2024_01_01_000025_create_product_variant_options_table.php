<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration for product_variant_options table
 * Links product variants to their specific variation option values
 * This allows fully dynamic variations per variant
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_variant_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_variant_id')->constrained('product_variants')->onDelete('cascade');
            $table->foreignId('variation_type_id')->constrained('variation_types')->onDelete('cascade');
            $table->foreignId('variation_option_id')->constrained('variation_options')->onDelete('cascade');
            $table->timestamps();

            // Each variant can only have one value per variation type
            $table->unique(['product_variant_id', 'variation_type_id'], 'variant_variation_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_variant_options');
    }
};
