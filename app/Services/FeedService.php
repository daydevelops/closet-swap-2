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

    public static function getItemFeed(
        $search = null,
        $filters = [],
        $tag = null,
        $sort = null,
        $page = 1
    ) : \Illuminate\Contracts\Pagination\LengthAwarePaginator {
        $query = ClothingItem::query()->with('images');
        $query = self::filterBlocked($query);

        if ($search) {
            $query->where('title', 'like', "%$search%");
        }

        if ($tag) {
            $query->whereHas('tags', fn ($q) => $q->where('name', $tag));
        }

        $safeFilters = array_intersect_key($filters, array_flip(self::ALLOWED_ITEM_FILTERS));
        foreach ($safeFilters as $key => $value) {
            $query->where($key, $value);
        }

        if ($sort === 'for-you' && auth()->check()) {
            self::applyForYouSort($query);
        } elseif ($sort === 'trending') {
            self::applyTrendingSort($query);
        } else {
            $query->latest();
        }

        return $query->paginate(20, ['*'], 'page', $page);
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

    private static function applyForYouSort($query) : void
    {
        $user = auth()->user();
        $likedTagIds = $user->likes()->with('tags')->get()
            ->flatMap(fn ($item) => $item->tags->pluck('id'))
            ->unique()
            ->values();

        if ($likedTagIds->isNotEmpty()) {
            $query->whereHas('tags', fn ($q) => $q->whereIn('ci_tags.id', $likedTagIds))
                  ->where('user_id', '!=', $user->id)
                  ->whereNotIn('id', $user->likes()->pluck('clothing_items.id'))
                  ->where('status', 'available')
                  ->latest();
        } else {
            $query->latest();
        }
    }

    private static function applyTrendingSort($query) : void
    {
        $query->withCount('likes')->orderBy('likes_count', 'desc');
    }

    private static function filterBlocked($query) : \Illuminate\Database\Eloquent\Builder
    {
        return auth()->check() ? $query->notBlocked() : $query;
    }
}
