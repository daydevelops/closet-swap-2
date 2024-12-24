<?php

namespace App\Http\Controllers;

use App\Services\FeedService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BrowseController extends Controller
{
    public function dashboard(Request $request) : \Inertia\Response
    {
        $feed = FeedService::getItemFeed(
            $request->query('search') ?? null,
            $request->query('filters') ?? []
        );
        return Inertia::render('Dashboard',compact('feed'));
    }

    public function wantedAds(Request $request) : \Inertia\Response
    {
        $feed = FeedService::getAdsFeed(
            $request->query('search') ?? null,
            $request->query('filters') ?? []
        );
        return Inertia::render('Wanted',compact('feed'));
    }
}
