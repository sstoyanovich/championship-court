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
        Schema::create('program_challenges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['stat', 'game_count', 'pxp']);
            $table->string('target_stat')->nullable(); // e.g., 'points', 'rebounds', 'assists'
            $table->integer('target_value'); // Goal amount
            $table->string('constraint_type')->nullable(); // 'team', 'position', etc.
            $table->string('constraint_value')->nullable(); // 'Miami Heat', 'PG', etc.
            $table->integer('stars_reward')->nullable(); // For star programs
            $table->integer('xp_reward')->nullable(); // For XP programs
            $table->text('description');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('program_challenges');
    }
};
