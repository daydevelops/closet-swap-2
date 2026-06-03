<?php

use App\Models\CiType;
use App\Models\User;
use Database\Factories\ClothingItemFactory;
use Database\Factories\ClothingItemImageFactory;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private array $adjectives = [
        'Vintage', 'Oversized', 'Cropped', 'Distressed', 'Ribbed',
        'Floral', 'Striped', 'Checked', 'Pleated', 'Quilted',
        'Sheer', 'Flowy', 'Structured', 'Faded', 'Patchwork',
        'Lace-trim', 'Washed', 'Slim-fit', 'Asymmetric', 'Embroidered',
    ];

    private array $brands = [
        "Levi's", 'Zara', 'H&M', 'Cos', 'Arket', 'Mango', 'Uniqlo',
        'Weekday', '& Other Stories', 'Free People', 'Reformation',
        'Urban Outfitters', 'Topshop', 'Pull&Bear', 'Bershka',
        'Stradivarius', 'Monki', 'Gap', 'Everlane', 'ASOS',
        'Thrifted', 'Vintage Find', 'Charity Shop', 'Handmade', 'No Brand',
    ];

    private array $descriptions = [
        'A great piece in excellent condition. Barely worn, just not my style anymore.',
        'Picked this up last season but it never really suited me. Perfect for someone who loves this aesthetic.',
        'Super comfortable and well-made. Has been loved but still has so much life left.',
        'One of my favourite pieces but I\'m having a clear-out. Would love to see it go to a good home.',
        'Fits true to size. Great quality fabric, no pilling or damage.',
        'Bought for a specific occasion and only worn once. Still looks brand new.',
        'A wardrobe staple I\'m letting go to make room for new things.',
        'Really unique piece — hard to find elsewhere. Selling because I\'ve moved on from this style.',
        'Worn a handful of times, washed carefully. No signs of wear.',
        'Perfect layering piece. Suits so many outfits.',
        'Absolute gem from a charity shop find. Outgrown it now.',
        'I loved this so much but I just don\'t reach for it anymore. Time to pass it on.',
        'Great condition, no stains, holes, or damage. Smoke-free home.',
        'Size runs a little large — fits more like the next size up.',
        'Selling as part of a wardrobe refresh. Too good to throw away.',
    ];

    public function up(): void
    {
        // Pre-load type data once
        // Note: ci_types.category and ci_sizes.category don't exist yet at this point —
        // they're added in a later migration. Shoe sizes are also seeded later.
        // The KAN-92 migration retroactively fixes item sizes for shoe types after seeding.
        $typeNames   = CiType::pluck('name', 'id')->toArray();
        $allSizeIds  = \App\Models\CiSize::pluck('id')->toArray();

        // --- Test user (known credentials for manual testing) ---
        $testUser = User::factory()->create([
            'name'  => 'Test User',
            'email' => 'mona@test.com',
        ]);

        for ($j = 0; $j < 20; $j++) {
            $typeId   = array_rand($typeNames);
            $typeName = $typeNames[$typeId];
            $adj      = $this->adjectives[array_rand($this->adjectives)];
            $item = ClothingItemFactory::new()->create([
                'user_id'     => $testUser->id,
                'title'       => "{$adj} {$typeName}",
                'description' => $this->descriptions[array_rand($this->descriptions)],
                'brand'       => $this->brands[array_rand($this->brands)],
                'ci_type_id'  => $typeId,
                'ci_size_id'  => $allSizeIds[array_rand($allSizeIds)],
                'status'      => $this->randomStatus(),
            ]);

            $imageCount = rand(1, 3);
            for ($k = 0; $k < $imageCount; $k++) {
                ClothingItemImageFactory::new()->create([
                    'clothing_item_id' => $item->id,
                ]);
            }
        }

        $demoUsers = [];

        // --- 20 random demo users ---
        for ($i = 1; $i <= 20; $i++) {
            $user = User::factory()->create([
                'email' => "demo.{$i}@demo.test",
            ]);

            $demoUsers[] = $user;

            $itemCount = rand(20, 50);

            for ($j = 0; $j < $itemCount; $j++) {
                $typeId   = array_rand($typeNames);
                $typeName = $typeNames[$typeId];
                $adj      = $this->adjectives[array_rand($this->adjectives)];
                $item = ClothingItemFactory::new()->create([
                    'user_id'     => $user->id,
                    'title'       => "{$adj} {$typeName}",
                    'description' => $this->descriptions[array_rand($this->descriptions)],
                    'brand'       => $this->brands[array_rand($this->brands)],
                    'ci_type_id'  => $typeId,
                    'ci_size_id'  => $allSizeIds[array_rand($allSizeIds)],
                    'status'      => $this->randomStatus(),
                ]);

                $imageCount = rand(0, 3);
                for ($k = 0; $k < $imageCount; $k++) {
                    ClothingItemImageFactory::new()->create([
                        'clothing_item_id' => $item->id,
                    ]);
                }
            }
        }

        // --- Seed likes ---
        // Give each demo user a handful of likes on other users' items
        $allItemIds = DB::table('clothing_items')
            ->where('status', 'available')
            ->pluck('id', 'user_id');

        foreach ($demoUsers as $demoUser) {
            $otherItems = DB::table('clothing_items')
                ->where('status', 'available')
                ->where('user_id', '!=', $demoUser->id)
                ->inRandomOrder()
                ->limit(rand(5, 15))
                ->pluck('id')
                ->toArray();
            $demoUser->likes()->syncWithoutDetaching($otherItems);
        }

        // Give the test user some likes too
        $testUserLikes = DB::table('clothing_items')
            ->where('status', 'available')
            ->where('user_id', '!=', $testUser->id)
            ->inRandomOrder()
            ->limit(10)
            ->pluck('id')
            ->toArray();
        $testUser->likes()->syncWithoutDetaching($testUserLikes);
    }

    public function down(): void
    {
        $userIds = DB::table('users')
            ->where(function ($q) {
                $q->where('email', 'like', '%@demo.test')
                  ->orWhere('email', 'mona@test.com');
            })
            ->pluck('id')
            ->toArray();

        if (empty($userIds)) return;

        DB::table('likes')->whereIn('user_id', $userIds)->delete();

        $itemIds = DB::table('clothing_items')
            ->whereIn('user_id', $userIds)
            ->pluck('id')
            ->toArray();

        if (!empty($itemIds)) {
            DB::table('likes')->whereIn('clothing_item_id', $itemIds)->delete();
            DB::table('clothing_item_images')->whereIn('clothing_item_id', $itemIds)->delete();
            DB::table('ci_color_item')->whereIn('clothing_item_id', $itemIds)->delete();
            DB::table('ci_material_item')->whereIn('clothing_item_id', $itemIds)->delete();
            DB::table('ci_tag_item')->whereIn('clothing_item_id', $itemIds)->delete();
            DB::table('clothing_items')->whereIn('id', $itemIds)->delete();
        }

        DB::table('users')->whereIn('id', $userIds)->delete();
    }

    private function randomStatus(): string
    {
        $roll = rand(1, 10);
        return match (true) {
            $roll <= 7  => 'available',
            $roll <= 9  => 'sold',
            default     => 'donated',
        };
    }
};
