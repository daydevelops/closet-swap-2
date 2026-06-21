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
        $query = ClothingItem::query()->with('images','size');
        $query = self::filterBlocked($query);

        if ($search) {
            $query->whereFullText('title', $search);
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
            $query->whereFullText('title', $search);
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
         * Always shows the full global feed (available items, excluding own),
         * ranked by a priority tier derived from the user's likes and follows:
         *
         *   !likes & !follows  →  latest (no personalisation)
         *   !likes &  follows  →  followed users first, then everyone else
         *    likes & !follows  →  tag-matched items first, then everyone else
         *    likes &  follows  →  tag+follow > tag only > follow only > rest
         *
         * The tag pool is the union of all tags across every item the user has
         * ever liked. Items the user has already liked are excluded when the
         * user has likes.
         */
        $user = auth('sanctum')->user();

        $likedTagIds = DB::table('ci_tag_item')
            ->join('likes', 'ci_tag_item.clothing_item_id', '=', 'likes.clothing_item_id')
            ->where('likes.user_id', $user->id)
            ->pluck('ci_tag_item.ci_tag_id')
            ->unique();

        $followedIds = $user->followings()->pluck('users.id');

        $hasLikes   = $likedTagIds->isNotEmpty();
        $hasFollows = $followedIds->isNotEmpty();

        $query->where('status', 'available')
              ->where('user_id', '!=', $user->id);

        if ($hasLikes) {
            $query->whereNotIn('id', $user->likes()->pluck('clothing_items.id'));
        }

        if ($hasLikes && $hasFollows) {
            $tPh = implode(',', array_fill(0, $likedTagIds->count(), '?'));
            $fPh = implode(',', array_fill(0, $followedIds->count(), '?'));
            $query->orderByRaw("
                CASE
                    WHEN user_id IN ($fPh)
                     AND EXISTS (
                         SELECT 1 FROM ci_tag_item
                         WHERE ci_tag_item.clothing_item_id = clothing_items.id
                           AND ci_tag_item.ci_tag_id IN ($tPh)
                     ) THEN 0
                    WHEN EXISTS (
                         SELECT 1 FROM ci_tag_item
                         WHERE ci_tag_item.clothing_item_id = clothing_items.id
                           AND ci_tag_item.ci_tag_id IN ($tPh)
                     ) THEN 1
                    WHEN user_id IN ($fPh) THEN 2
                    ELSE 3
                END
            ", array_merge(
                $followedIds->values()->toArray(),
                $likedTagIds->values()->toArray(),
                $likedTagIds->values()->toArray(),
                $followedIds->values()->toArray(),
            ));
        } elseif ($hasLikes) {
            $tPh = implode(',', array_fill(0, $likedTagIds->count(), '?'));
            $query->orderByRaw("
                CASE
                    WHEN EXISTS (
                        SELECT 1 FROM ci_tag_item
                        WHERE ci_tag_item.clothing_item_id = clothing_items.id
                          AND ci_tag_item.ci_tag_id IN ($tPh)
                    ) THEN 0
                    ELSE 1
                END
            ", $likedTagIds->values()->toArray());
        } elseif ($hasFollows) {
            $fPh = implode(',', array_fill(0, $followedIds->count(), '?'));
            $query->orderByRaw(
                "CASE WHEN user_id IN ($fPh) THEN 0 ELSE 1 END",
                $followedIds->values()->toArray()
            );
        }

        $query->latest();
    }

    private static function applyLatestSort($query) : void
    {
        $query->where('status', 'available');

        if (auth('sanctum')->check()) {
            $user = auth('sanctum')->user();
            $query->where('user_id', '!=', $user->id);

            $followedIds = $user->followings()->pluck('users.id');
            if ($followedIds->isNotEmpty()) {
                $placeholders = implode(',', array_fill(0, $followedIds->count(), '?'));
                $query->orderByRaw(
                    "CASE WHEN user_id IN ($placeholders) THEN 0 ELSE 1 END",
                    $followedIds->values()->toArray()
                );
            }
        }

        $query->latest();
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
