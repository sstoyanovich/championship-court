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
        Schema::create('user_collection_rewards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('collection_reward_id')->constrained()->onDelete('cascade');
            $table->timestamp('claimed_at')->useCurrent();
            $table->timestamps();

            // Ensure a user can only claim each reward once
            $table->unique(['user_id', 'collection_reward_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_collection_rewards');
    }
};
