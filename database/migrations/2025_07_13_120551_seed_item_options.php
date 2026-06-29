<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        $now = now();

        $insert = fn($table, $values) =>
        DB::table($table)->insert(array_map(fn($name) => ['name' => $name, 'created_at' => $now, 'updated_at' => $now], $values));

        $insert('ci_types', [
            // Tops
            'T-Shirt', 'Blouse', 'Shirt', 'Tank Top', 'Crop Top', 'Hoodie', 'Sweatshirt', 'Sweater', 'Cardigan',
            // Bottoms
            'Jeans', 'Trousers', 'Shorts', 'Skirt', 'Leggings',
            // Full pieces
            'Dress', 'Jumpsuit', 'Romper', 'Co-ord Set',
            // Outerwear
            'Jacket', 'Coat', 'Blazer', 'Puffer', 'Trench Coat', 'Vest',
            // Footwear
            'Sneakers', 'Boots', 'Heels', 'Sandals', 'Loafers', 'Flats',
            // Accessories
            'Bag', 'Belt', 'Hat', 'Scarf', 'Sunglasses', 'Jewellery', 'Watch',
            // Other
            'Other',
        ]);

        $insert('ci_genders', ['Femme', 'Masc', 'Gender Neutral']);

        $insert('ci_sizes', ['XXS', 'XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL', 'One Size']);

        $insert('ci_units', ['US', 'EU', 'UK', 'IT', 'FR', 'JP', 'AU']);

        $insert('ci_fits', ['Slim', 'Regular', 'Relaxed', 'Oversized', 'Fitted', 'Cropped', 'Flared']);

        $insert('ci_conditions', ['New with tags', 'New without tags', 'Excellent', 'Good', 'Fair', 'Well loved']);

        $insert('ci_tags', [
            // Aesthetics
            'Goth',  'Boho', 'Indie', 'Academia',
            'Streetwear', 'Vintage', 'Retro', '90s', '80s', '70s',
            'Grunge', 'Preppy', 'Minimalist',
            'Punk', 'Kawaii', 'Alt', 'Skater',
            'Coastal Grandmother', 'Fairycore', 'Military',
            'Techwear', 'Athleisure',
            // Occasion
            'Casual', 'Formal', 'Party', 'Workwear', 'Festival', 'Bridal',
            // Style notes
            'Statement Piece', 'Basics', 'Designer', 'Handmade', 'Upcycled',
        ]);

        $insert('ci_colors', [
            'Black', 'White', 'Off-White', 'Cream', 'Beige',
            'Grey', 'Charcoal', 'Silver',
            'Brown', 'Tan', 'Camel',
            'Navy', 'Blue', 'Light Blue', 'Cobalt',
            'Green', 'Olive', 'Sage', 'Emerald', 'Mint',
            'Red', 'Burgundy', 'Maroon',
            'Pink', 'Hot Pink', 'Blush', 'Mauve',
            'Purple', 'Lavender', 'Lilac',
            'Yellow', 'Mustard', 'Gold',
            'Orange', 'Rust', 'Coral',
            'Multicolour', 'Print', 'Tie-Dye',
        ]);

        $insert('ci_materials', [
            'Cotton', 'Organic Cotton', 'Linen', 'Silk', 'Satin',
            'Wool', 'Merino Wool', 'Cashmere', 'Mohair',
            'Denim', 'Leather', 'Faux Leather', 'Suede',
            'Polyester', 'Nylon', 'Spandex', 'Lycra', 'Elastane',
            'Velvet', 'Corduroy', 'Tweed', 'Chiffon', 'Lace',
            'Fleece', 'Terry Cloth', 'Jersey', 'Knit',
            'Recycled Fabric', 'Bamboo', 'Tencel',
        ]);
    }

    public function down(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('ci_types')->truncate();
        DB::table('ci_genders')->truncate();
        DB::table('ci_sizes')->truncate();
        DB::table('ci_units')->truncate();
        DB::table('ci_fits')->truncate();
        DB::table('ci_conditions')->truncate();
        DB::table('ci_tags')->truncate();
        DB::table('ci_colors')->truncate();
        DB::table('ci_materials')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
};
