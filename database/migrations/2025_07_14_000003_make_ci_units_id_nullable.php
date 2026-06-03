<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clothing_items', function (Blueprint $table) {
            $table->unsignedBigInteger('ci_units_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        // Nullify any NULLs before removing nullable — use first available unit as fallback
        $fallback = \Illuminate\Support\Facades\DB::table('ci_units')->value('id');
        if ($fallback) {
            \Illuminate\Support\Facades\DB::table('clothing_items')
                ->whereNull('ci_units_id')
                ->update(['ci_units_id' => $fallback]);
        }

        Schema::table('clothing_items', function (Blueprint $table) {
            $table->unsignedBigInteger('ci_units_id')->nullable(false)->change();
        });
    }
};
