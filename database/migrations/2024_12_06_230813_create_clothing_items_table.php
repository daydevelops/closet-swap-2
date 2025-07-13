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
            $table->foreignId('gender_id')->constrained();
            $table->foreignId('size_id')->constrained();
            $table->foreignId('units_id')->constrained();
            $table->foreignId('fit_id')->constrained();
            $table->foreignId('condition_id')->constrained();
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
