<?php

namespace App\Http\Controllers;

use App\Services\FeedService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BrowseController extends Controller
{
    public function dashboard(Request $request) : JsonResponse
    {
        $feed = FeedService::getItemFeed(
            $request->query('search') ?? null,
            $request->query('filters') ?? []
        )->toArray();
        return response()->json($feed);
    }

    public function wantedAds(Request $request) : JsonResponse
    {
        $feed = FeedService::getAdsFeed(
            $request->query('search') ?? null,
            $request->query('filters') ?? []
        );
        return response()->json($feed);
    }
}
