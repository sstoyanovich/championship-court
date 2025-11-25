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
        Schema::create('packs', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g., "Standard Pack", "Premium Choice Pack"
            $table->enum('type', ['standard', 'choice'])->default('standard');
            $table->text('description')->nullable();
            $table->integer('card_count')->default(4); // How many cards in the pack
            $table->integer('choice_count')->nullable(); // For choice packs: how many can be selected
            $table->json('odds_config'); // JSON with tier odds (e.g., {"bronze": 60, "silver": 30, "gold": 8, "emerald": 1.5, "sapphire": 0.4, "amethyst": 0.08, "diamond": 0.015, "pink_diamond": 0.005})
            $table->integer('cost')->default(1000); // Cost in stubs (for future currency system)
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('packs');
    }
};
