<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class BlockController extends Controller
{
    public function store(Request $request, User $user) : void
    {
        $user->block();
    }

    public function destroy(Request $request, User $user) : void
    {
        $user->unblock();
    }

}
