<?php

namespace App\Models;

class CiColors extends CiOption
{
    protected $table = 'ci_colors';

    public function clothingItems()
    {
        return $this->belongsToMany(ClothingItem::class,'ci_color_item');
    }
}
