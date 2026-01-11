<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Update product_variants table to remove hardcoded variation fields
 * Variations will now be managed through product_variant_options pivot table
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            // Remove hardcoded variation fields
            $table->dropColumn(['color', 'size', 'material']);
        });
    }

    public function down(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            $table->string('color')->nullable()->after('variant_name');
            $table->string('size')->nullable()->after('color');
            $table->string('material')->nullable()->after('size');
        });
    }
};
