<?php

namespace App\Models;

class CiMaterial extends CiOption
{
    protected $table = 'ci_materials';

    public function clothingItems()
    {
        return $this->belongsToMany(ClothingItem::class, 'ci_material_item');
    }
}
