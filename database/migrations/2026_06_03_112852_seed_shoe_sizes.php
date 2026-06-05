<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $sizes = [];
        $now   = now();

        // US Women's: 4 to 18 in half steps
        foreach ($this->halfSteps(4, 18) as $n) {
            $sizes[] = ['name' => "US Women's {$n}", 'category' => 'shoe', 'created_at' => $now, 'updated_at' => $now];
        }

        // US Men's: 4 to 17 in half steps
        foreach ($this->halfSteps(4, 17) as $n) {
            $sizes[] = ['name' => "US Men's {$n}", 'category' => 'shoe', 'created_at' => $now, 'updated_at' => $now];
        }

        // EU: 34 to 52 whole numbers
        foreach (range(34, 52) as $n) {
            $sizes[] = ['name' => "EU {$n}", 'category' => 'shoe', 'created_at' => $now, 'updated_at' => $now];
        }

        DB::table('ci_sizes')->insert($sizes);

        // Retroactively fix existing items — the seeder ran before the category column existed,
        // so items with shoe types got clothing sizes. Reassign them a random shoe size now.
        $shoeTypeIds = DB::table('ci_types')->where('category', 'shoe')->pluck('id')->toArray();
        $shoeSizeIds = DB::table('ci_sizes')->where('category', 'shoe')->pluck('id')->toArray();

        if (!empty($shoeTypeIds) && !empty($shoeSizeIds)) {
            $shoeItems = DB::table('clothing_items')
                ->whereIn('ci_type_id', $shoeTypeIds)
                ->pluck('id');

            foreach ($shoeItems as $itemId) {
                DB::table('clothing_items')
                    ->where('id', $itemId)
                    ->update(['ci_size_id' => $shoeSizeIds[array_rand($shoeSizeIds)]]);
            }
        }
    }

    public function down(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('ci_sizes')->where('category', 'shoe')->delete();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }

    private function halfSteps(float $from, float $to): array
    {
        $steps = [];
        for ($n = $from; $n <= $to; $n += 0.5) {
            // Format: drop the .0 for whole numbers, keep .5
            $steps[] = ($n == floor($n)) ? (string)(int)$n : number_format($n, 1);
        }
        return $steps;
    }
};
