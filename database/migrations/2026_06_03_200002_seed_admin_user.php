<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')->insert([
            'id'                => (string) Str::uuid(),
            'name'              => 'Admin',
            'email'             => env('ADMIN_EMAIL'),
            'email_verified_at' => now(),
            'password'          => Hash::make(env('ADMIN_PASSWORD')),
            'is_admin'          => true,
            'created_at'        => now(),
            'updated_at'        => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('users')->where('email', env('ADMIN_EMAIL'))->delete();
    }
};
