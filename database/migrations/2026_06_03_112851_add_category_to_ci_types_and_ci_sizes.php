<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ci_types', function (Blueprint $table) {
            $table->string('category')->default('clothing')->after('name');
        });

        Schema::table('ci_sizes', function (Blueprint $table) {
            $table->string('category')->default('clothing')->after('name');
        });

        // Categorise existing types
        DB::table('ci_types')
            ->whereIn('name', ['Sneakers', 'Boots', 'Heels', 'Sandals', 'Loafers', 'Flats'])
            ->update(['category' => 'shoe']);

        DB::table('ci_types')
            ->whereIn('name', ['Bag'])
            ->update(['category' => 'bag']);

        DB::table('ci_types')
            ->whereIn('name', ['Belt', 'Hat', 'Scarf', 'Sunglasses', 'Jewellery', 'Watch'])
            ->update(['category' => 'accessory']);
    }

    public function down(): void
    {
        Schema::table('ci_types', function (Blueprint $table) {
            $table->dropColumn('category');
        });

        Schema::table('ci_sizes', function (Blueprint $table) {
            $table->dropColumn('category');
        });
    }
};
