<?php

namespace App\Services;

use App\Models\ClothingItem;

class FeedService
{
    public static function getFeed($search = null, $filters = [])
    {
        $query = ClothingItem::with('images');
        if ($search) {
            $query->where('name', 'like', "%$search%");
        }
        foreach ($filters as $key => $value) {
            $query->where($key, $value);
        }
        return $query->get();
    }
}
