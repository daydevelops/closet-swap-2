<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $childTables = [
        'clothing_item_images',
        'likes',
        'ci_color_item',
        'ci_material_item',
        'ci_tag_item',
    ];

    public function up(): void
    {
        // Drop FK constraints so MySQL 8 allows the column type change
        foreach ($this->childTables as $tbl) {
            Schema::table($tbl, function (Blueprint $table) use ($tbl) {
                $table->dropForeign("{$tbl}_clothing_item_id_foreign");
            });
        }

        // PK — raw SQL to strip AUTO_INCREMENT and change type
        DB::statement('ALTER TABLE clothing_items MODIFY id char(36) NOT NULL');

        // FK columns
        foreach ($this->childTables as $tbl) {
            Schema::table($tbl, function (Blueprint $table) {
                $table->uuid('clothing_item_id')->nullable(false)->change();
            });
        }

        // Re-add FK constraints
        foreach ($this->childTables as $tbl) {
            Schema::table($tbl, function (Blueprint $table) {
                $table->foreign('clothing_item_id')->references('id')->on('clothing_items');
            });
        }
    }

    public function down(): void
    {
        foreach ($this->childTables as $tbl) {
            Schema::table($tbl, function (Blueprint $table) use ($tbl) {
                $table->dropForeign("{$tbl}_clothing_item_id_foreign");
            });
        }

        DB::statement('ALTER TABLE clothing_items MODIFY id bigint unsigned NOT NULL AUTO_INCREMENT');

        foreach ($this->childTables as $tbl) {
            DB::statement("ALTER TABLE {$tbl} MODIFY clothing_item_id bigint unsigned NOT NULL");
        }

        foreach ($this->childTables as $tbl) {
            Schema::table($tbl, function (Blueprint $table) {
                $table->foreign('clothing_item_id')->references('id')->on('clothing_items');
            });
        }
    }
};
