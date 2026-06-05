<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->integer('items_given_count')->default(0)->after('is_admin');
        });

        // Backfill from existing items
        DB::statement("
            UPDATE users u
            SET items_given_count = (
                SELECT COUNT(*) FROM clothing_items ci
                WHERE ci.user_id = u.id
                AND ci.status IN ('swapped', 'donated', 'sold')
            )
        ");
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('items_given_count');
        });
    }
};
