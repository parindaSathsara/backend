<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration for variation_options table
 * Stores dynamic color, size, material options that admin can manage
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('variation_options', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['color', 'size', 'material'])->index();
            $table->string('name'); // e.g., "Red", "Small", "Cotton"
            $table->string('value')->nullable(); // For colors: hex code like "#FF0000"
            $table->string('display_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->unique(['type', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('variation_options');
    }
};
