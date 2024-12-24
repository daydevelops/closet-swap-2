<?php

namespace App\Services;

use App\Models\ClothingItem;
use App\Models\WantedAd;

class FeedService
{
    public static function getItemFeed($search = null, $filters = []) : \Illuminate\Database\Eloquent\Collection
    {
        $query = ClothingItem::query();
        $query = self::filterBlocked($query);
        $query->with('images');
        if ($search) {
            $query->where('name', 'like', "%$search%");
        }
        foreach ($filters as $key => $value) {
            $query->where($key, $value);
        }
        return $query->get();
    }

    public static function getAdsFeed($search = null, $filters = []) : \Illuminate\Database\Eloquent\Collection
    {
        $query = WantedAd::query();
        $query = self::filterBlocked($query);
        if ($search) {
            $query->where('title', 'like', "%$search%");
        }
        foreach ($filters as $key => $value) {
            $query->where($key, $value);
        }
        return $query->get();
    }

    private static function filterBlocked($query) : \Illuminate\Database\Eloquent\Builder
    {
        return auth()->check() ? $query->notBlocked() : $query;
    }
}
