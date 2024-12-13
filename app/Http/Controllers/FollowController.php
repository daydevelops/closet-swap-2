<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class FollowController extends Controller
{

    public function followers(Request $request, User $user)
    {
        return $user->followers;
    }

    public function following(Request $request, User $user)
    {
        return $user->followings;
    }

    public function follow(Request $request, User $user)
    {
        auth()->user()->follow($user);

        return response()->json(null, 201);
    }

    public function unfollow(Request $request, User $user)
    {
        auth()->user()->unfollow($user);

        return response()->json(null, 204);
    }
}
