<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreClothingItemRequest;
use App\Http\Requests\UpdateClothingItemRequest;
use App\Models\ClothingItem;

class ClothingItemController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreClothingItemRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(ClothingItem $clothingItem)
    {
        //
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

    /**
     * Get options for clothing items.
     */
    public function options()
    {
        // This method should return options for clothing items, such as categories, sizes, etc.
        // Implement the logic to fetch and return the options.
        return response()->json([
            'types' => ['T-Shirts', 'Jeans', 'Jackets', 'Shoes'],
            'sizes' => ['XS', 'S', 'M', 'L', 'XL'],
            "fits" => ['true to size', 'slim fit', 'regular fit', 'oversized'],
            "conditions" => ['new', 'like new', 'used', 'vintage'],
            "units" => ['in width', 'in length', 'in height', 'US', 'EU'],
            "tags" => ['casual', 'formal', 'sportswear', 'vintage', 'streetwear'],
            "colors" => ['red', 'blue', 'green', 'yellow', 'black', 'white'],
            "materials" => ['cotton', 'wool', 'leather', 'polyester', 'silk'],
            "genders" => ['masc', 'fem', 'unisex'],
        ], 200);

    }
}
