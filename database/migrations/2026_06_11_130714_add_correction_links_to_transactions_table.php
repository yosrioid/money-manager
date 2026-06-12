<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->foreignId('reverses_transaction_id')->nullable()->after('posted_at')
                ->constrained('transactions')->nullOnDelete();
            $table->foreignId('replaces_transaction_id')->nullable()->after('reverses_transaction_id')
                ->constrained('transactions')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reverses_transaction_id');
            $table->dropConstrainedForeignId('replaces_transaction_id');
        });
    }
};
