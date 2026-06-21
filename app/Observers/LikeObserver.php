<?php

namespace App\Observers;

use App\Models\ClothingItem;
use App\Models\Like;

class LikeObserver
{
    public function created(Like $like): void
    {
        ClothingItem::where('id', $like->clothing_item_id)->increment('likes_count');
    }

    public function deleted(Like $like): void
    {
        ClothingItem::where('id', $like->clothing_item_id)
            ->where('likes_count', '>', 0)
            ->decrement('likes_count');
    }
}
