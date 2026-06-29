<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clothing_items', function (Blueprint $table) {
            $table->unsignedInteger('likes_count')->default(0)->after('status');
        });

        DB::statement('UPDATE clothing_items ci SET likes_count = (SELECT COUNT(*) FROM likes WHERE clothing_item_id = ci.id)');
    }

    public function down(): void
    {
        Schema::table('clothing_items', function (Blueprint $table) {
            $table->dropColumn('likes_count');
        });
    }
};
