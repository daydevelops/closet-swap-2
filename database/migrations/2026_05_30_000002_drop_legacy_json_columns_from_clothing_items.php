<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clothing_items', function (Blueprint $table) {
            $table->dropColumn(['colors', 'materials', 'tags']);
        });
    }

    public function down(): void
    {
        Schema::table('clothing_items', function (Blueprint $table) {
            $table->json('colors')->nullable();
            $table->json('materials')->nullable();
            $table->json('tags')->nullable();
        });
    }
};
