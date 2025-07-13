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

        Schema::create('tags_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clothing_item_id')->constrained()->onDelete('cascade');
            $table->foreignId('tag_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });

        Schema::create('materials_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clothing_item_id')->constrained()->onDelete('cascade');
            $table->foreignId('material_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });

        Schema::create('colors_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clothing_item_id')->constrained()->onDelete('cascade');
            $table->foreignId('color_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tags_items');
        Schema::dropIfExists('materials_items');
        Schema::dropIfExists('colors_items');
    }
};
