<?php

namespace App\Http\Controllers;

use App\Services\FeedService;
use App\Services\ImageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BrowseController extends Controller
{
    public function dashboard(Request $request) : JsonResponse
    {
        $feed = FeedService::getItemFeed(
            $request->query('search') ?? null,
            $request->query('filters') ?? []
        );

        $result = $feed->map(function ($item) {
            $data = $item->toArray();
            $data['images'] = ImageService::signedUrls($item->images);
            return $data;
        });

        return response()->json($result);
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
