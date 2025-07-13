<?php

namespace App\Models;

class CiTags extends CiOption
{
    protected $table = 'ci_tags';

    public function clothingItems()
    {
        return $this->belongsToMany(ClothingItem::class,'ci_tag_item');
    }
}
