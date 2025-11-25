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
        // For SQLite, we need to recreate the table to modify enum
        // For other databases, we would alter the column
        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            // SQLite approach: recreate table with new constraint
            Schema::table('program_challenges', function (Blueprint $table) {
                $table->string('constraint_type_new')->nullable();
            });

            DB::statement('UPDATE program_challenges SET constraint_type_new = constraint_type');

            Schema::table('program_challenges', function (Blueprint $table) {
                $table->dropColumn('constraint_type');
            });

            Schema::table('program_challenges', function (Blueprint $table) {
                $table->renameColumn('constraint_type_new', 'constraint_type');
            });
        } else {
            // For MySQL/PostgreSQL
            DB::statement("ALTER TABLE program_challenges MODIFY constraint_type ENUM('position', 'team', 'tier', 'player') NULL");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert is not strictly necessary as we're just allowing more values
        // But for completeness:
        $driver = DB::getDriverName();

        if ($driver !== 'sqlite') {
            DB::statement("ALTER TABLE program_challenges MODIFY constraint_type ENUM('position', 'team', 'tier') NULL");
        }
    }
};
