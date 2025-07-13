<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        $now = now();

        $insert = fn($table, $values) =>
        DB::table($table)->insert(array_map(fn($name) => ['name' => $name, 'created_at' => $now, 'updated_at' => $now], $values));

        $insert('ci_types', ['Shirt', 'Pants', 'Jacket', 'Shoes']);
        $insert('ci_genders', ['masc', 'femme', 'unisex']);
        $insert('ci_sizes', ['XS', 'S', 'M', 'L', 'XL']);
        $insert('ci_units', ['US', 'EU', 'UK']);
        $insert('ci_fits', ['Tight', 'Regular', 'Loose']);
        $insert('ci_conditions', ['New', 'Gently Used', 'Used', 'Worn']);
        $insert('ci_tags', ['Casual', 'Formal', 'Vintage', 'Trendy']);
        $insert('ci_colors', ['Red', 'Blue', 'Green', 'Black', 'White']);
        $insert('ci_materials', ['Cotton', 'Wool', 'Leather', 'Polyester', 'Silk']);
    }

    public function down(): void
    {
        DB::table('ci_types')->truncate();
        DB::table('ci_genders')->truncate();
        DB::table('ci_sizes')->truncate();
        DB::table('ci_units')->truncate();
        DB::table('ci_fits')->truncate();
        DB::table('ci_conditions')->truncate();
        DB::table('ci_tags')->truncate();
        DB::table('ci_colors')->truncate();
        DB::table('ci_materials')->truncate();
    }
};
