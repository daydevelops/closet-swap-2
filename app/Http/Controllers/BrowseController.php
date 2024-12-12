<?php

namespace App\Http\Controllers;

use App\Services\FeedService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BrowseController extends Controller
{
    public function dashboard(Request $request)
    {
        $feed = FeedService::getFeed(
            $request->query('search') ?? null,
            $request->query('filters') ?? []
        );
        return Inertia::render('Dashboard',compact('feed'));
    }
}
