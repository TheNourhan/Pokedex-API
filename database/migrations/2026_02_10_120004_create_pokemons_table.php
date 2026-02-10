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
        Schema::create('pokemons', function (Blueprint $table) {
            $table->id();
            $table->integer('external_id')->unique()->comment('ID from PokeAPI');
            $table->string('name')->index();
            $table->integer('height')->nullable();
            $table->integer('weight')->nullable();
            $table->integer('base_experience')->nullable();
            $table->integer('order')->nullable();
            $table->string('species')->nullable();
            $table->string('form')->nullable();
            $table->json('sprites')->nullable();
            $table->boolean('is_default')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pokemons');
    }
};
