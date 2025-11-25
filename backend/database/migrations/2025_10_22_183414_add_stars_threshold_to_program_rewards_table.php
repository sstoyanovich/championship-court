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
            $table->integer('stars_threshold')->nullable()->after('xp_threshold');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('program_rewards', function (Blueprint $table) {
            $table->dropColumn('stars_threshold');
        });
    }
};
