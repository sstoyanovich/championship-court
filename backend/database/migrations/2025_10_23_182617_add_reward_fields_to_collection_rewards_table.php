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
        Schema::table('collection_rewards', function (Blueprint $table) {
            // Add new columns after collection_id
            $table->integer('required_cards')->after('collection_id')->default(1);
            $table->foreignId('player_id')->nullable()->after('reward_quantity')->constrained()->onDelete('set null');
            $table->foreignId('pack_id')->nullable()->after('player_id')->constrained()->onDelete('set null');
            $table->integer('order')->after('pack_id')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('collection_rewards', function (Blueprint $table) {
            $table->dropForeign(['player_id']);
            $table->dropForeign(['pack_id']);
            $table->dropColumn(['required_cards', 'player_id', 'pack_id', 'order']);
        });
    }
};
