<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->decimal('exchange_rate', 24, 10)->nullable()->after('currency_code');
        });

        Schema::table('transaction_entries', function (Blueprint $table) {
            $table->bigInteger('base_amount')->nullable()->after('amount');
        });

        DB::table('transaction_entries')->update(['base_amount' => DB::raw('amount')]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn('exchange_rate');
        });

        Schema::table('transaction_entries', function (Blueprint $table) {
            $table->dropColumn('base_amount');
        });
    }
};
