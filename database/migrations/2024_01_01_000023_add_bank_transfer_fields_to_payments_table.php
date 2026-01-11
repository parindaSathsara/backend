<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration to add bank transfer payment fields
 * Supports payment slip uploads for bank transfers
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            // Add bank transfer specific fields
            $table->string('payment_slip')->nullable()->after('gateway_response'); // Path to uploaded slip
            $table->string('bank_reference')->nullable()->after('payment_slip'); // Customer's bank reference
            $table->timestamp('slip_uploaded_at')->nullable()->after('bank_reference');
            $table->timestamp('verified_at')->nullable()->after('slip_uploaded_at');
            $table->foreignId('verified_by')->nullable()->after('verified_at')->constrained('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['verified_by']);
            $table->dropColumn([
                'payment_slip',
                'bank_reference',
                'slip_uploaded_at',
                'verified_at',
                'verified_by'
            ]);
        });
    }
};
