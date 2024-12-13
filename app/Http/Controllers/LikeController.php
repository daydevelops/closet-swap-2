<?php

namespace App\Http\Controllers;

use App\Models\ClothingItem;
use Illuminate\Http\Request;

class LikeController extends Controller
{

    public function getItemLikes(ClothingItem $clothingItem)
    {
        return response()->json($clothingItem->likes);
    }

    public function getMyLikes(Request $request)
    {
        return response()->json($request->user()->likes);
    }

    public function store(Request $request, ClothingItem $clothingItem)
    {
        $request->user()->likes()->attach($clothingItem->id);

        return response()->json(['message' => 'Clothing item liked']);
    }

    public function destroy(Request $request, ClothingItem $clothingItem)
    {
        $request->user()->likes()->detach($clothingItem->id);

        return response()->json(['message' => 'Clothing item unliked']);
    }

}
