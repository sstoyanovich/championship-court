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
        Schema::table('packs', function (Blueprint $table) {
            // JSON field to define guaranteed tier minimums for specific slots
            // Example: [{"slot": 5, "min_tier": "emerald"}] means slot 5 must be at least emerald
            $table->json('guaranteed_slots')->nullable()->after('odds_config');

            // Foreign key to restrict pack to cards from a specific collection
            $table->foreignId('collection_id')->nullable()->after('guaranteed_slots')->constrained()->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('packs', function (Blueprint $table) {
            $table->dropForeign(['collection_id']);
            $table->dropColumn(['guaranteed_slots', 'collection_id']);
        });
    }
};
