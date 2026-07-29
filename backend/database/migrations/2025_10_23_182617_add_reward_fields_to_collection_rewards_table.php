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
        // These columns may already exist in the table creation migration
        // Only add them if they don't exist to avoid duplicate column errors
        $needsUpdate = false;
        $columnsToAdd = [];
        
        if (!Schema::hasColumn('collection_rewards', 'required_cards')) {
            $columnsToAdd['required_cards'] = true;
            $needsUpdate = true;
        }
        if (!Schema::hasColumn('collection_rewards', 'player_id')) {
            $columnsToAdd['player_id'] = true;
            $needsUpdate = true;
        }
        if (!Schema::hasColumn('collection_rewards', 'pack_id')) {
            $columnsToAdd['pack_id'] = true;
            $needsUpdate = true;
        }
        if (!Schema::hasColumn('collection_rewards', 'order')) {
            $columnsToAdd['order'] = true;
            $needsUpdate = true;
        }
        
        if ($needsUpdate) {
            Schema::table('collection_rewards', function (Blueprint $table) use ($columnsToAdd) {
                if (isset($columnsToAdd['required_cards'])) {
                    $table->integer('required_cards')->after('collection_id')->default(1);
                }
                if (isset($columnsToAdd['player_id'])) {
                    $table->foreignId('player_id')->nullable()->after('reward_quantity')->constrained()->onDelete('set null');
                }
                if (isset($columnsToAdd['pack_id'])) {
                    $table->foreignId('pack_id')->nullable()->after('player_id')->constrained()->onDelete('set null');
                }
                if (isset($columnsToAdd['order'])) {
                    $table->integer('order')->after('pack_id')->default(0);
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('collection_rewards', function (Blueprint $table) {
            // Only drop columns if they exist
            if (Schema::hasColumn('collection_rewards', 'player_id')) {
                $table->dropForeign(['player_id']);
            }
            if (Schema::hasColumn('collection_rewards', 'pack_id')) {
                $table->dropForeign(['pack_id']);
            }
            
            $columnsToDrop = [];
            if (Schema::hasColumn('collection_rewards', 'required_cards')) {
                $columnsToDrop[] = 'required_cards';
            }
            if (Schema::hasColumn('collection_rewards', 'player_id')) {
                $columnsToDrop[] = 'player_id';
            }
            if (Schema::hasColumn('collection_rewards', 'pack_id')) {
                $columnsToDrop[] = 'pack_id';
            }
            if (Schema::hasColumn('collection_rewards', 'order')) {
                $columnsToDrop[] = 'order';
            }
            
            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};
