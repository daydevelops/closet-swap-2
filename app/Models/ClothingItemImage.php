<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClothingItemImage extends Model
{
    /** @use HasFactory<\Database\Factories\ClothingItemImageFactory> */
    use HasFactory;

    protected $fillable = [
        'clothing_item_id',
        'image_url',
    ];

    public function clothingItem()
    {
        return $this->belongsTo(ClothingItem::class);
    }
}
