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
        Schema::create('transaction_tags', function (Blueprint $table) {
            $table->foreignId('workspace_id')->constrained('workspaces')->restrictOnDelete();
            $table->foreignId('transaction_id')->constrained('transactions')->restrictOnDelete();
            $table->foreignId('tag_id')->constrained('tags')->restrictOnDelete();
            $table->timestamps();

            $table->unique(['transaction_id', 'tag_id']);
            $table->index(['workspace_id', 'tag_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaction_tags');
    }
};
