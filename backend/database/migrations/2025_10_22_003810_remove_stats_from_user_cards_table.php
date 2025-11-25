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
        Schema::table('user_cards', function (Blueprint $table) {
            $table->dropColumn([
                'games_played',
                'total_minutes',
                'total_points',
                'total_rebounds',
                'total_assists',
                'total_steals',
                'total_blocks',
                'total_turnovers',
                'total_fgm',
                'total_fga',
                'total_3pm',
                'total_3pa',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_cards', function (Blueprint $table) {
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
        });
    }
};
