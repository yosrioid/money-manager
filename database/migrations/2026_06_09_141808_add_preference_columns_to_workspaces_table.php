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
        Schema::table('workspaces', function (Blueprint $table) {
            $table->char('default_currency', 3)->default('IDR')->after('name');
            $table->string('timezone', 60)->default('Asia/Jakarta')->after('default_currency');
            $table->string('locale', 10)->default('id')->after('timezone');
            $table->string('number_format', 20)->default('id-ID')->after('locale');
            $table->unsignedSmallInteger('first_day_of_week')->default(1)->after('number_format');
            $table->unsignedSmallInteger('month_start_day')->default(1)->after('first_day_of_week');
            $table->boolean('adjust_month_for_weekend')->default(false)->after('month_start_day');
        });
    }

    public function down(): void
    {
        Schema::table('workspaces', function (Blueprint $table) {
            $table->dropColumn([
                'default_currency',
                'timezone',
                'locale',
                'number_format',
                'first_day_of_week',
                'month_start_day',
                'adjust_month_for_weekend',
            ]);
        });
    }
};
