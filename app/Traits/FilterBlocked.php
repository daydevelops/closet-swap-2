<?php

namespace App\Traits;

trait FilterBlocked
{
    public function scopeNotBlocked($query)
    {
        $authUser = auth()->user();
        return $query->whereDoesntHave('user', function ($query) use ($authUser) {
            $query->whereIn('id', $authUser->blocks->pluck('id'))
                ->orWhereIn('id', $authUser->blockedBy->pluck('id'));
        });
    }
}
