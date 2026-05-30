<?php

namespace App\Services;

use App\Models\ClothingItem;
use App\Models\WantedAd;

class FeedService
{
    // Only these columns may be used as filter keys — prevents arbitrary column injection
    private const ALLOWED_ITEM_FILTERS = [
        'ci_type_id', 'ci_gender_id', 'ci_size_id', 'ci_units_id',
        'ci_fit_id', 'ci_condition_id', 'status', 'brand',
    ];

    private const ALLOWED_AD_FILTERS = [
        'category',
    ];

    public static function getItemFeed($search = null, $filters = []) : \Illuminate\Database\Eloquent\Collection
    {
        $query = ClothingItem::query();
        $query = self::filterBlocked($query);
        $query->with('images');
        if ($search) {
            $query->where('title', 'like', "%$search%");
        }
        $safeFilters = array_intersect_key($filters, array_flip(self::ALLOWED_ITEM_FILTERS));
        foreach ($safeFilters as $key => $value) {
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
        $safeFilters = array_intersect_key($filters, array_flip(self::ALLOWED_AD_FILTERS));
        foreach ($safeFilters as $key => $value) {
            $query->where($key, $value);
        }
        return $query->get();
    }

    private static function filterBlocked($query) : \Illuminate\Database\Eloquent\Builder
    {
        return auth()->check() ? $query->notBlocked() : $query;
    }
}
