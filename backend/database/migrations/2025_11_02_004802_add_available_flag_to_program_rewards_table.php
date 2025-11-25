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
        Schema::table('program_rewards', function (Blueprint $table) {
            // Flag to indicate if the reward is available to claim
            // False means reward is "coming soon" - earned but not yet claimable
            $table->boolean('available')->default(true)->after('description');

            // Optional label for unavailable rewards (e.g., "All-Star", "Finest")
            $table->string('coming_soon_label')->nullable()->after('available');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('program_rewards', function (Blueprint $table) {
            $table->dropColumn(['available', 'coming_soon_label']);
        });
    }
};
