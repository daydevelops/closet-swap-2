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
        $paginated = FeedService::getItemFeed(
            $request->query('search'),
            $request->query('filters') ?? [],
            $request->query('tag'),
            $request->query('sort'),
            $request->query('page', 1)
        );

        $paginated->getCollection()->transform(function ($item) {
            $data = $item->toArray();
            $data['images'] = ImageService::signedUrls($item->images);
            return $data;
        });

        return response()->json($paginated);
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
