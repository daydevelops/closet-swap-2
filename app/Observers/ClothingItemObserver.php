<?php

namespace App\Observers;

use App\Models\ClothingItem;

class ClothingItemObserver
{
    private const COMPLETED = ['swapped', 'donated', 'sold'];

    public function updated(ClothingItem $item): void
    {
        if (!$item->wasChanged('status')) {
            return;
        }

        $wasCompleted = in_array($item->getOriginal('status'), self::COMPLETED);
        $nowCompleted = in_array($item->status, self::COMPLETED);

        if (!$wasCompleted && $nowCompleted) {
            $item->user()->increment('items_given_count');
        } elseif ($wasCompleted && !$nowCompleted) {
            $item->user()->where('items_given_count', '>', 0)->decrement('items_given_count');
        }
    }
}
