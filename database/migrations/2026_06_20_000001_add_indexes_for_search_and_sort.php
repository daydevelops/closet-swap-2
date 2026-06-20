<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clothing_items', function (Blueprint $table) {
            $table->fullText('title');
            $table->index(['status', 'created_at']);
        });

        Schema::table('wanted_ads', function (Blueprint $table) {
            $table->fullText('title');
        });

        Schema::table('ci_tag_item', function (Blueprint $table) {
            $table->index(['clothing_item_id', 'ci_tag_id']);
        });

        Schema::table('ci_material_item', function (Blueprint $table) {
            $table->index(['clothing_item_id', 'ci_material_id']);
        });

        Schema::table('ci_color_item', function (Blueprint $table) {
            $table->index(['clothing_item_id', 'ci_color_id']);
        });
    }

    public function down(): void
    {
        // MySQL dropped the auto-created single-column FK indexes on clothing_item_id when
        // it saw our composite indexes cover the same column. We must restore them before
        // dropping the composites, otherwise MySQL blocks the drop (error 1553).
        foreach (['ci_color_item', 'ci_material_item', 'ci_tag_item'] as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->index('clothing_item_id');
            });
        }

        Schema::table('ci_color_item', function (Blueprint $table) {
            $table->dropIndex(['clothing_item_id', 'ci_color_id']);
        });

        Schema::table('ci_material_item', function (Blueprint $table) {
            $table->dropIndex(['clothing_item_id', 'ci_material_id']);
        });

        Schema::table('ci_tag_item', function (Blueprint $table) {
            $table->dropIndex(['clothing_item_id', 'ci_tag_id']);
        });

        Schema::table('wanted_ads', function (Blueprint $table) {
            $table->dropFullText(['title']);
        });

        Schema::table('clothing_items', function (Blueprint $table) {
            $table->dropIndex(['status', 'created_at']);
            $table->dropFullText(['title']);
        });
    }
};
