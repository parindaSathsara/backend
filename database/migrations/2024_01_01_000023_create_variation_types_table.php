<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration for variation_types table
 * Stores dynamic variation type definitions (color, size, material, gold_type, etc.)
 * Admin can add new variation types as needed
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('variation_types', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g., "Color", "Size", "Material", "Gold Type"
            $table->string('slug')->unique(); // e.g., "color", "size", "material", "gold_type"
            $table->string('input_type')->default('select'); // select, color_picker, text
            $table->boolean('is_required')->default(false);
            $table->boolean('is_active')->default(true);
            $table->integer('display_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('variation_types');
    }
};
