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
        Schema::create('program_rewards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_id')->constrained()->onDelete('cascade');
            $table->integer('xp_threshold')->nullable(); // For XP programs, NULL for star programs
            $table->enum('reward_type', ['pack', 'stubs', 'player']);
            $table->foreignId('reward_id')->nullable(); // References player_id or pack_id
            $table->integer('reward_amount')->default(1); // Quantity of stubs or items
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('program_rewards');
    }
};
