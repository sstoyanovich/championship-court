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
        Schema::create('collection_rewards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('collection_id')->constrained()->onDelete('cascade');
            $table->integer('required_cards'); // Number of locked cards needed to earn this reward
            $table->string('reward_type'); // "stubs", "player_card", "pack"
            $table->string('reward_value')->nullable(); // For stubs: amount (deprecated in favor of reward_quantity)
            $table->integer('reward_quantity')->default(1); // For stubs: amount, for cards/packs: quantity
            $table->foreignId('player_id')->nullable()->constrained()->onDelete('set null'); // For player card rewards
            $table->foreignId('pack_id')->nullable()->constrained()->onDelete('set null'); // For pack rewards
            $table->integer('order')->default(0); // Display order
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('collection_rewards');
    }
};
