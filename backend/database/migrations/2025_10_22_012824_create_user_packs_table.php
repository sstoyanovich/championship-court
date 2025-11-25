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
        Schema::create('user_packs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('pack_id')->constrained()->onDelete('cascade');
            $table->string('source')->default('purchase'); // 'purchase', 'reward', 'gift'
            $table->boolean('opened')->default(false);
            $table->timestamp('obtained_at')->useCurrent();
            $table->timestamp('opened_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'opened']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_packs');
    }
};
