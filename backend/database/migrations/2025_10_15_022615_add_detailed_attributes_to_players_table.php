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
        Schema::table('players', function (Blueprint $table) {
            // Category ratings (aggregated scores)
            $table->integer('outside_scoring')->default(70);
            $table->integer('inside_scoring')->default(70);
            $table->integer('defense')->default(70);
            $table->integer('athleticism')->default(70);
            $table->integer('playmaking')->default(70);
            $table->integer('rebounding')->default(70);

            // Outside Scoring attributes
            $table->integer('close_shot')->default(70);
            $table->integer('mid_range_shot')->default(65);
            $table->integer('three_point_shot')->default(60);
            $table->integer('free_throw')->default(70);
            $table->integer('shot_iq')->default(65);
            $table->integer('offensive_consistency')->default(70);

            // Inside Scoring attributes
            $table->integer('layup')->default(70);
            $table->integer('standing_dunk')->default(40);
            $table->integer('driving_dunk')->default(40);
            $table->integer('post_hook')->default(50);
            $table->integer('post_fade')->default(50);
            $table->integer('post_control')->default(50);
            $table->integer('draw_foul')->default(60);
            $table->integer('hands')->default(70);

            // Defense attributes
            $table->integer('interior_defense')->default(50);
            $table->integer('perimeter_defense')->default(60);
            $table->integer('steal')->default(55);
            $table->integer('block')->default(40);
            $table->integer('help_defense_iq')->default(60);
            $table->integer('pass_perception')->default(60);
            $table->integer('defensive_consistency')->default(65);

            // Athleticism attributes
            $table->integer('speed')->default(70);
            $table->integer('agility')->default(70);
            $table->integer('strength')->default(60);
            $table->integer('vertical')->default(65);
            $table->integer('stamina')->default(80);
            $table->integer('hustle')->default(75);
            $table->integer('overall_durability')->default(75);

            // Playmaking attributes
            $table->integer('pass_accuracy')->default(65);
            $table->integer('ball_handle')->default(65);
            $table->integer('speed_with_ball')->default(70);
            $table->integer('pass_iq')->default(65);
            $table->integer('pass_vision')->default(65);

            // Rebounding attributes
            $table->integer('offensive_rebound')->default(50);
            $table->integer('defensive_rebound')->default(55);

            // Other attributes
            $table->integer('intangibles')->default(70);
            $table->string('potential')->default('C');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('players', function (Blueprint $table) {
            $table->dropColumn([
                'outside_scoring',
                'inside_scoring',
                'defense',
                'athleticism',
                'playmaking',
                'rebounding',
                'close_shot',
                'mid_range_shot',
                'three_point_shot',
                'free_throw',
                'shot_iq',
                'offensive_consistency',
                'layup',
                'standing_dunk',
                'driving_dunk',
                'post_hook',
                'post_fade',
                'post_control',
                'draw_foul',
                'hands',
                'interior_defense',
                'perimeter_defense',
                'steal',
                'block',
                'help_defense_iq',
                'pass_perception',
                'defensive_consistency',
                'speed',
                'agility',
                'strength',
                'vertical',
                'stamina',
                'hustle',
                'overall_durability',
                'pass_accuracy',
                'ball_handle',
                'speed_with_ball',
                'pass_iq',
                'pass_vision',
                'offensive_rebound',
                'defensive_rebound',
                'intangibles',
                'potential'
            ]);
        });
    }
};
