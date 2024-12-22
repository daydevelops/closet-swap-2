<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WantedAd;

class WantedAdPolicy
{
    /**
     * Create a new policy instance.
     */
    public function __construct()
    {
        //
    }

    public function update(User $user, WantedAd $wantedAd) : bool
    {
        return $user->id === $wantedAd->user_id;
    }

    public function delete(User $user, WantedAd $wantedAd) : bool
    {
        return $user->id === $wantedAd->user_id;
    }
}
