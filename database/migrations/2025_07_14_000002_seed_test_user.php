<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'mona@test.com',
        ]);
    }

    public function down(): void
    {
        User::where('email', 'mona@test.com')->delete();
    }
};
