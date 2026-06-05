<?php

namespace App\Services;

use App\Models\ClothingItem;
use App\Models\WantedAd;
use Illuminate\Support\Facades\DB;

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
        $query = ClothingItem::query()->with('images')->withCount('likes');
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

        if ($sort === 'for-you' && auth('sanctum')->check()) {
            self::applyForYouSort($query);
        } elseif ($sort === 'trending') {
            self::applyTrendingSort($query);
        } else {
            self::applyLatestSort($query);
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
        /*
         * For You algorithm
         *
         * We collect every tag from every item the user has ever liked — not just
         * from one item, but the full union across all likes. For example, if the
         * user liked two items tagged [Party, Vintage] and [Y2K, Cottagecore],
         * the tag pool becomes {Party, Vintage, Y2K, Cottagecore}.
         *
         * We then surface any available item that shares at least one tag with
         * that pool, ordered by recency. An item does not need to have a specific
         * tag the user once liked — it just needs to overlap with any tag from
         * any liked item.
         *
         * Excluded from results:
         *  - Items owned by the authenticated user
         *  - Items the user has already liked
         *  - Items with status != available
         *
         * Falls back to the standard latest feed if the user has no likes yet.
         */
        $user = auth('sanctum')->user();
        $likedTagIds = DB::table('ci_tag_item')
            ->join('likes', 'ci_tag_item.clothing_item_id', '=', 'likes.clothing_item_id')
            ->where('likes.user_id', $user->id)
            ->pluck('ci_tag_item.ci_tag_id')
            ->unique();

        if ($likedTagIds->isNotEmpty()) {
            $query->whereHas('tags', fn ($q) => $q->whereIn('ci_tags.id', $likedTagIds))
                  ->where('user_id', '!=', $user->id)
                  ->whereNotIn('id', $user->likes()->pluck('clothing_items.id'))
                  ->where('status', 'available')
                  ->latest();
        } else {
            self::applyLatestSort($query);
        }
    }

    private static function applyLatestSort($query) : void
    {
        $query->where('status', 'available')
              ->latest();

        if (auth('sanctum')->check()) {
            $query->where('user_id', '!=', auth('sanctum')->id());
        }
    }

    private static function applyTrendingSort($query) : void
    {
        $query->where('status', 'available')
              ->orderBy('likes_count', 'desc');

        if (auth('sanctum')->check()) {
            $query->where('user_id', '!=', auth('sanctum')->id());
        }
    }

    private static function filterBlocked($query) : \Illuminate\Database\Eloquent\Builder
    {
        return auth('sanctum')->check() ? $query->notBlocked() : $query;
    }
}
