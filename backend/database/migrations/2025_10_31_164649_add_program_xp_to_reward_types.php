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
        // SQLite doesn't support modifying enum values directly
        // The reward_type column is stored as TEXT in SQLite
        // So we can just start using 'program_xp' without modifying the schema

        // For MySQL/PostgreSQL, you would need to alter the enum
        // But since we're using SQLite, no changes needed to the schema

        // The validation rules in the controller will handle the new type
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No schema changes to revert
    }
};
