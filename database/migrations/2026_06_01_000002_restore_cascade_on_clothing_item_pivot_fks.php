<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $pivotTables = [
        'ci_color_item',
        'ci_material_item',
        'ci_tag_item',
    ];

    public function up(): void
    {
        foreach ($this->pivotTables as $tbl) {
            Schema::table($tbl, function (Blueprint $table) use ($tbl) {
                $table->dropForeign("{$tbl}_clothing_item_id_foreign");
                $table->foreign('clothing_item_id')->references('id')->on('clothing_items')->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        foreach ($this->pivotTables as $tbl) {
            Schema::table($tbl, function (Blueprint $table) use ($tbl) {
                $table->dropForeign("{$tbl}_clothing_item_id_foreign");
                $table->foreign('clothing_item_id')->references('id')->on('clothing_items');
            });
        }
    }
};
