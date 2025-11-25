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
        // Add count column with default value of 1
        Schema::table('user_cards', function (Blueprint $table) {
            $table->integer('count')->default(1)->after('locked_at');
        });

        // Consolidate duplicate cards
        $this->consolidateDuplicateCards();
    }

    /**
     * Consolidate duplicate user_cards records by summing counts
     */
    private function consolidateDuplicateCards(): void
    {
        $duplicates = DB::table('user_cards')
            ->select('user_id', 'player_id', DB::raw('COUNT(*) as duplicate_count'))
            ->groupBy('user_id', 'player_id')
            ->having('duplicate_count', '>', 1)
            ->get();

        foreach ($duplicates as $duplicate) {
            // Get all duplicate records for this user-player combination
            $records = DB::table('user_cards')
                ->where('user_id', $duplicate->user_id)
                ->where('player_id', $duplicate->player_id)
                ->orderBy('id')
                ->get();

            if ($records->count() > 1) {
                // Keep the first record and update its count
                $keepRecord = $records->first();
                $totalCount = $records->count();
                
                DB::table('user_cards')
                    ->where('id', $keepRecord->id)
                    ->update(['count' => $totalCount]);

                // Delete all other duplicate records
                $deleteIds = $records->skip(1)->pluck('id')->toArray();
                DB::table('user_cards')->whereIn('id', $deleteIds)->delete();
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_cards', function (Blueprint $table) {
            $table->dropColumn('count');
        });
    }
};
