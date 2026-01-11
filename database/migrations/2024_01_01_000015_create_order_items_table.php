<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration for order_items table
 * Stores individual items in an order
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('variant_id')->nullable()->constrained('product_variants')->onDelete('set null');
            $table->foreignId('album_id')->nullable()->constrained()->onDelete('set null');
            $table->string('item_type'); // 'product' or 'album'
            $table->string('name'); // Store name at time of purchase
            $table->string('sku')->nullable();
            $table->integer('quantity');
            $table->decimal('price', 10, 2); // Price at time of purchase
            $table->decimal('subtotal', 10, 2);
            $table->json('meta_data')->nullable(); // Store variant details, etc.
            $table->timestamps();

            // Index for performance
            $table->index('order_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
