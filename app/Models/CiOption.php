<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CiOption extends Model
{
    protected $fillable = [
        'name',
    ];

    public function clothingItems()
    {
        return $this->hasMany(ClothingItem::class);
    }
}
