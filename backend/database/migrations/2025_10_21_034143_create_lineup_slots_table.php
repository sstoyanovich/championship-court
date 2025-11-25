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
        Schema::create('lineup_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lineup_id')->constrained()->onDelete('cascade');
            $table->string('position', 10);
            $table->foreignId('user_card_id')->nullable()->constrained()->onDelete('cascade');
            $table->timestamps();

            // Ensure each position only appears once per lineup
            $table->unique(['lineup_id', 'position']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lineup_slots');
    }
};
