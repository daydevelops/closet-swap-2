<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreClothingItemRequest;
use App\Http\Requests\UpdateClothingItemRequest;
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
            'types' => CiType::select('id','name')->get(),
            'sizes' => CiSize::select('id','name')->get(),
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
            'brand'           => $request->brand,
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
        $item = $clothingItem->load(['images', 'type', 'size', 'fit', 'condition', 'units', 'gender']);
        $images = ImageService::signedUrls($item->images);
        return response()->json([
            'images' => $images,
            'item' => $item
        ], 200);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ClothingItem $clothingItem)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateClothingItemRequest $request, ClothingItem $clothingItem)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ClothingItem $clothingItem)
    {
        //
    }
}
