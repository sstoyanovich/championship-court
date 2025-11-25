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
            // Flag to indicate if this card can be obtained from packs
            // If false, card is program-exclusive (can only be earned as rewards)
            $table->boolean('obtainable_from_packs')->default(true)->after('collection_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('players', function (Blueprint $table) {
            $table->dropColumn('obtainable_from_packs');
        });
    }
};
