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
        Schema::create('collections', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g., "Live Series", "Veteran", "Legends"
            $table->string('type'); // e.g., "team", "season", "special"
            $table->string('sub_collection')->nullable(); // e.g., "Lakers", "Warriors" for team collections
            $table->text('description')->nullable();
            $table->integer('total_items')->default(0); // Total cards in this collection
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('collections');
    }
};
