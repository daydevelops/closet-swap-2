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
        Schema::create('clothing_items', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('description');
            $table->string('brand');
            $table->foreignId('ci_type_id')->constrained();
            $table->foreignId('ci_gender_id')->constrained();
            $table->foreignId('ci_size_id')->constrained();
            $table->foreignId('ci_units_id')->constrained();
            $table->foreignId('ci_fit_id')->constrained();
            $table->foreignId('ci_condition_id')->constrained();
            $table->json('tags')->nullable();
            $table->json('materials')->nullable();
            $table->json('colors')->nullable();
            $table->foreignId('user_id')->constrained();
            $table->string('status');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clothing_items');
    }
};
