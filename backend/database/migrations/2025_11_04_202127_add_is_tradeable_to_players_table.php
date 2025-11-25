<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add is_tradeable column (default true for normal cards)
        Schema::table('players', function (Blueprint $table) {
            $table->boolean('is_tradeable')->default(true)->after('obtainable_from_packs');
        });

        // Mark program-exclusive/reward cards as non-tradeable
        // These are cards that can only be obtained through programs/rewards
        DB::table('players')
            ->where('obtainable_from_packs', false)
            ->update(['is_tradeable' => false]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('players', function (Blueprint $table) {
            $table->dropColumn('is_tradeable');
        });
    }
};
