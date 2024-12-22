<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreWantedAdRequest;
use App\Models\WantedAd;
use App\Services\FeedService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class WantedAdController extends Controller
{

    public function index(Request $request) : \Inertia\Response
    {
        $feed = FeedService::getAdsFeed(
            $request->query('search') ?? null,
            $request->query('filters') ?? []
        );
        return Inertia::render('Wanted',compact('feed'));
    }

    public function store(StoreWantedAdRequest $request) : void
    {
        $ad = WantedAd::create([
            'title' => $request->input('title'),
            'description' => $request->input('description'),
            'category' => $request->input('category'),
            'user_id' => auth()->id(),
        ]);
    }

    public function update(StoreWantedAdRequest $request, WantedAd $wantedAd) : void
    {
        if (!auth()->user()->can('update', $wantedAd)) {
            abort(403);
        }

        $wantedAd->update([
            'title' => $request->input('title'),
            'description' => $request->input('description'),
            'category' => $request->input('category'),
        ]);
    }

    public function destroy(WantedAd $wantedAd) : void
    {
        if (!auth()->user()->can('delete', $wantedAd)) {
            abort(403);
        }

        $wantedAd->delete();
    }
}
