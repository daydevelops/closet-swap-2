<?php

namespace App\Http\Controllers;

use App\Models\ClothingItem;
use App\Services\ImageService;
use Illuminate\Http\Request;

class LikeController extends Controller
{

    public function getItemLikes(ClothingItem $clothingItem)
    {
        return response()->json($clothingItem->likes);
    }

    public function getMyLikes(Request $request)
    {
        $paginated = $request->user()->likes()
            ->with('images')
            ->latest('likes.created_at')
            ->paginate(20);

        $paginated->getCollection()->transform(function ($item) {
            $data = $item->toArray();
            $data['images'] = ImageService::signedUrls($item->images);
            return $data;
        });

        return response()->json($paginated);
    }

    public function store(Request $request, ClothingItem $clothingItem)
    {
        $request->user()->likes()->syncWithoutDetaching([$clothingItem->id]);

        return response()->json(['message' => 'Clothing item liked']);
    }

    public function destroy(Request $request, ClothingItem $clothingItem)
    {
        $request->user()->likes()->detach($clothingItem->id);

        return response()->json(['message' => 'Clothing item unliked']);
    }

}
