<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreClothingItemRequest;
use App\Http\Requests\UpdateClothingItemRequest;
use Illuminate\Http\Request;
use App\Models\ClothingItem;
use App\Models\CiType;
use App\Models\CiSize;
use App\Models\CiFit;
use App\Models\CiCondition;
use App\Models\CiUnit;
use App\Models\CiTags;
use App\Models\CiColors;
use App\Models\CiMaterial;
use App\Models\CiGender;
use App\Services\ImageService;

class ClothingItemController extends Controller
{
    public function create()
    {
        return response()->json([
            'types'      => CiType::select('id', 'name', 'category')->get(),
            'sizes'      => CiSize::where('category', 'clothing')->select('id', 'name')->get(),
            'shoe_sizes' => CiSize::where('category', 'shoe')->select('id', 'name')->get(),
            'fits' => CiFit::select('id','name')->get(),
            'conditions' => CiCondition::select('id','name')->get(),
            'units' => CiUnit::select('id','name')->get(),
            'genders' => CiGender::select('id','name')->get(),
            'tags' => CiTags::pluck('name')->toArray(),
            'colors' => CiColors::pluck('name')->toArray(),
            'materials' => CiMaterial::pluck('name')->toArray(),
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreClothingItemRequest $request)
    {
        $item = ClothingItem::create([
            'title'           => $request->title,
            'description'     => $request->description,
            'ci_type_id'      => $request->type,
            'ci_gender_id'    => $request->gender,
            'ci_size_id'      => $request->size,
            'ci_fit_id'       => $request->fit,
            'ci_condition_id' => $request->condition,
            'ci_units_id'     => $request->units,
            'brand'           => $request->brand ?: 'Unknown',
            'user_id'         => $request->user()->id,
            'status'          => 'available',
        ]);

        if ($request->colors) {
            $item->colors()->attach(CiColors::whereIn('name', $request->colors)->pluck('id'));
        }
        if ($request->materials) {
            $item->materials()->attach(CiMaterial::whereIn('name', $request->materials)->pluck('id'));
        }
        if ($request->tags) {
            $item->tags()->attach(CiTags::whereIn('name', $request->tags)->pluck('id'));
        }
        if ($request->hasFile('pictures')) {
            $image_service = new ImageService();
            foreach ($request->file('pictures') as $file) {
                $path = $image_service->upload($file);
                if ($path) {
                    $item->images()->create(['path' => $path]);
                } else {
                    return response()->json(['message' => 'Image upload failed'], 500);
                }
            }
        }
        return response()->json([
            'message' => 'Clothing item created successfully',
            'id' => $item->id,
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(ClothingItem $clothingItem)
    {
        $clothingItem->load(['user', 'images', 'type', 'size', 'fit', 'condition', 'units', 'gender', 'colors', 'materials', 'tags']);
        $images = ImageService::signedUrls($clothingItem->images);

        $item = $clothingItem->toArray();
        // Return many-to-many as name strings (frontend expects string[])
        $item['tags']      = $clothingItem->tags->pluck('name')->toArray();
        $item['colors']    = $clothingItem->colors->pluck('name')->toArray();
        $item['materials'] = $clothingItem->materials->pluck('name')->toArray();
        // Return only public-safe user fields (avoid exposing email)
        if ($clothingItem->user) {
            $item['user'] = [
                'id'         => $clothingItem->user->id,
                'name'       => $clothingItem->user->name,
                'avatar_url' => $clothingItem->user->avatar_url,
            ];

            $item['user']['contact_handle'] = $clothingItem->user->contact_handle;
        }

        $item['liked'] = auth('sanctum')->check()
            ? auth('sanctum')->user()->likes()->where('clothing_item_id', $clothingItem->id)->exists()
            : false;

        return response()->json(['images' => $images, 'item' => $item], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateClothingItemRequest $request, ClothingItem $clothingItem)
    {
        if (!auth()->user()->can('update', $clothingItem)) {
            abort(403);
        }

        $data = [];
        if ($request->has('title'))       $data['title']           = $request->title;
        if ($request->has('description')) $data['description']     = $request->description;
        if ($request->has('type'))        $data['ci_type_id']      = $request->type;
        if ($request->has('gender'))      $data['ci_gender_id']    = $request->gender;
        if ($request->has('size'))        $data['ci_size_id']      = $request->size;
        if ($request->has('fit'))          $data['ci_fit_id']        = $request->fit;
        if ($request->has('condition'))   $data['ci_condition_id'] = $request->condition;
        if ($request->has('units'))       $data['ci_units_id']     = $request->units;
        if ($request->has('brand'))       $data['brand']           = $request->brand;

        if (!empty($data)) {
            $clothingItem->update($data);
        }

        if ($request->has('colors')) {
            $clothingItem->colors()->sync(CiColors::whereIn('name', $request->colors)->pluck('id'));
        }
        if ($request->has('materials')) {
            $clothingItem->materials()->sync(CiMaterial::whereIn('name', $request->materials)->pluck('id'));
        }
        if ($request->has('tags')) {
            $clothingItem->tags()->sync(CiTags::whereIn('name', $request->tags)->pluck('id'));
        }

        return response()->json(['message' => 'Item updated successfully'], 200);
    }

    public function updateStatus(Request $request, ClothingItem $clothingItem)
    {
        if (!auth()->user()->can('update', $clothingItem)) {
            abort(403);
        }

        $request->validate([
            'status' => 'required|in:available,sold,donated,swapped',
        ]);

        $clothingItem->update(['status' => $request->status]);

        return response()->json(['message' => 'Status updated successfully'], 200);
    }

    public function addImages(Request $request, ClothingItem $clothingItem)
    {
        if (!auth()->user()->can('update', $clothingItem)) {
            abort(403);
        }

        $request->validate([
            'pictures'   => 'required|array|min:1',
            'pictures.*' => 'file|mimes:jpg,jpeg,png,PNG,webp|max:5120',
        ]);

        $existing = $clothingItem->images()->count();
        $incoming = count($request->file('pictures'));
        $maxPhotos = config('items.max_photos');
        if ($existing + $incoming > $maxPhotos) {
            return response()->json(['message' => "Items are limited to {$maxPhotos} photos."], 422);
        }

        $imageService = new ImageService();
        $added = [];

        foreach ($request->file('pictures') as $file) {
            $path = $imageService->upload($file);
            if (!$path) {
                return response()->json(['message' => 'Image upload failed'], 500);
            }
            $image = $clothingItem->images()->create(['path' => $path]);
            $added[] = [
                'id'         => $image->id,
                'signed_url' => ImageService::signedUrl($path),
            ];
        }

        return response()->json($added, 201);
    }

    public function destroyImage(ClothingItem $clothingItem, \App\Models\ClothingItemImage $image)
    {
        if (!auth()->user()->can('update', $clothingItem)) {
            abort(403);
        }

        if ($image->clothing_item_id !== $clothingItem->id) {
            abort(404);
        }

        ImageService::delete($image->path);
        $image->delete();

        return response()->json(['message' => 'Image deleted successfully'], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ClothingItem $clothingItem)
    {
        if (!auth()->user()->can('delete', $clothingItem)) {
            abort(403);
        }

        foreach ($clothingItem->images as $image) {
            ImageService::delete($image->path);
            $image->delete();
        }

        $clothingItem->likes()->detach();
        $clothingItem->colors()->detach();
        $clothingItem->materials()->detach();
        $clothingItem->tags()->detach();

        $clothingItem->delete();

        return response()->json(['message' => 'Item deleted successfully'], 200);
    }
}
