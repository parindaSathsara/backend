<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Update variation_options to support dynamic variation types
 */
return new class extends Migration
{
    public function up(): void
    {
        // First, ensure variation_types table has initial data
        $colorType = DB::table('variation_types')->where('slug', 'color')->value('id');
        if (!$colorType) {
            $colorType = DB::table('variation_types')->insertGetId([
                'name' => 'Color',
                'slug' => 'color',
                'input_type' => 'color_picker',
                'is_required' => false,
                'is_active' => true,
                'display_order' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $sizeType = DB::table('variation_types')->where('slug', 'size')->value('id');
        if (!$sizeType) {
            $sizeType = DB::table('variation_types')->insertGetId([
                'name' => 'Size',
                'slug' => 'size',
                'input_type' => 'select',
                'is_required' => false,
                'is_active' => true,
                'display_order' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $materialType = DB::table('variation_types')->where('slug', 'material')->value('id');
        if (!$materialType) {
            $materialType = DB::table('variation_types')->insertGetId([
                'name' => 'Material',
                'slug' => 'material',
                'input_type' => 'select',
                'is_required' => false,
                'is_active' => true,
                'display_order' => 3,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Add variation_type_id column if it doesn't exist
        if (!Schema::hasColumn('variation_options', 'variation_type_id')) {
            Schema::table('variation_options', function (Blueprint $table) {
                $table->unsignedBigInteger('variation_type_id')->nullable()->after('id');
            });
        }

        // Migrate existing data based on type column if type column exists
        if (Schema::hasColumn('variation_options', 'type')) {
            DB::statement("UPDATE variation_options SET variation_type_id = ? WHERE type = 'color' AND variation_type_id IS NULL", [$colorType]);
            DB::statement("UPDATE variation_options SET variation_type_id = ? WHERE type = 'size' AND variation_type_id IS NULL", [$sizeType]);
            DB::statement("UPDATE variation_options SET variation_type_id = ? WHERE type = 'material' AND variation_type_id IS NULL", [$materialType]);

            // Now make variation_type_id NOT NULL
            DB::statement("ALTER TABLE variation_options MODIFY variation_type_id BIGINT UNSIGNED NOT NULL");

            // Add foreign key constraint if it doesn't exist
            $foreignKeys = DB::select("
                SELECT CONSTRAINT_NAME 
                FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
                WHERE TABLE_NAME = 'variation_options' 
                AND COLUMN_NAME = 'variation_type_id' 
                AND CONSTRAINT_NAME LIKE '%foreign%'
            ");

            if (empty($foreignKeys)) {
                Schema::table('variation_options', function (Blueprint $table) {
                    $table->foreign('variation_type_id')->references('id')->on('variation_types')->onDelete('cascade');
                });
            }

            // Drop old unique constraint if it exists
            try {
                DB::statement('ALTER TABLE variation_options DROP INDEX variation_options_type_name_unique');
            } catch (\Exception $e) {
                // Index may not exist, that's okay
            }

            // Drop old type column
            Schema::table('variation_options', function (Blueprint $table) {
                $table->dropColumn('type');
            });
        }

        // Add new unique constraint if it doesn't exist
        $uniqueIndexes = DB::select("
            SELECT CONSTRAINT_NAME 
            FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS 
            WHERE TABLE_NAME = 'variation_options' 
            AND CONSTRAINT_NAME = 'variation_options_variation_type_id_name_unique'
        ");

        if (empty($uniqueIndexes)) {
            Schema::table('variation_options', function (Blueprint $table) {
                $table->unique(['variation_type_id', 'name']);
            });
        }
    }

    public function down(): void
    {
        // Drop new unique constraint
        try {
            Schema::table('variation_options', function (Blueprint $table) {
                $table->dropUnique(['variation_type_id', 'name']);
            });
        } catch (\Exception $e) {
            // May not exist
        }

        // Drop foreign key
        try {
            Schema::table('variation_options', function (Blueprint $table) {
                $table->dropForeign(['variation_type_id']);
            });
        } catch (\Exception $e) {
            // May not exist
        }

        // Add back type column
        if (!Schema::hasColumn('variation_options', 'type')) {
            Schema::table('variation_options', function (Blueprint $table) {
                $table->enum('type', ['color', 'size', 'material'])->after('id');
            });
        }

        // Migrate data back if possible
        if (Schema::hasColumn('variation_options', 'variation_type_id')) {
            DB::statement("
                UPDATE variation_options vo
                JOIN variation_types vt ON vo.variation_type_id = vt.id
                SET vo.type = vt.slug
                WHERE vt.slug IN ('color', 'size', 'material')
            ");
        }

        // Drop variation_type_id column
        if (Schema::hasColumn('variation_options', 'variation_type_id')) {
            Schema::table('variation_options', function (Blueprint $table) {
                $table->dropColumn('variation_type_id');
            });
        }

        // Re-add old unique constraint
        try {
            Schema::table('variation_options', function (Blueprint $table) {
                $table->unique(['type', 'name']);
            });
        } catch (\Exception $e) {
            // May already exist
        }
    }
};
