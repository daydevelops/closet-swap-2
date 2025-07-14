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

    protected $casts = [
        'materials' => 'array',
        'colors' => 'array',
        'tags' => 'array',
    ];

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

    public function colors()
    {
        return $this->belongsToMany(CiColors::class, 'ci_color_item', 'clothing_item_id', 'ci_color_id');
    }

    public function materials()
    {
        return $this->belongsToMany(CiMaterial::class, 'ci_material_item', 'clothing_item_id', 'ci_material_id');
    }

    public function type()
    {
        return $this->belongsTo(CiType::class, 'ci_type_id');
    }

    public function gender()
    {
        return $this->belongsTo(CiGender::class, 'ci_gender_id');
    }

    public function size()
    {
        return $this->belongsTo(CiSize::class, 'ci_size_id');
    }

    public function units()
    {
        return $this->belongsTo(CiUnit::class, 'ci_units_id');
    }

    public function fit()
    {
        return $this->belongsTo(CiFit::class, 'ci_fit_id');
    }

    public function condition()
    {
        return $this->belongsTo(CiCondition::class, 'ci_condition_id');
    }


}
