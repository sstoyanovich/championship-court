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
        Schema::create('user_player_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('player_id')->constrained()->onDelete('cascade');
            $table->integer('player_xp')->default(0); // PXP
            $table->integer('games_played')->default(0);
            $table->integer('total_minutes')->default(0);
            $table->integer('total_points')->default(0);
            $table->integer('total_rebounds')->default(0);
            $table->integer('total_assists')->default(0);
            $table->integer('total_steals')->default(0);
            $table->integer('total_blocks')->default(0);
            $table->integer('total_turnovers')->default(0);
            $table->integer('total_fgm')->default(0);
            $table->integer('total_fga')->default(0);
            $table->integer('total_3pm')->default(0);
            $table->integer('total_3pa')->default(0);
            $table->timestamps();

            $table->unique(['user_id', 'player_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_player_stats');
    }
};
