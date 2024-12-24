<?php

namespace App\Models;

use App\Traits\FilterBlocked;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClothingItem extends Model
{
    /** @use HasFactory<\Database\Factories\ClothingItemFactory> */
    use HasFactory;
    use FilterBlocked;

    protected $guarded = [];

    public function images()
    {
        return $this->hasMany(ClothingItemImage::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function likes()
    {
        return $this->belongsToMany(User::class, 'likes');
    }
}
