<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Storage;

class ImageService
{
    public function upload($image) {
        if ($image) {
            return $image->store('images', 's3');
        }
        return null;
    }

    /**
     * Map a collection of ClothingItemImage models to signed URL arrays.
     * External URLs (e.g. picsum placeholders) are returned as-is.
     */
    public static function signedUrls(Collection $images): array
    {
        return $images->map(fn ($image) => [
            'id'         => $image->id,
            'signed_url' => str_starts_with($image->path, 'http')
                ? $image->path
                : Storage::disk('s3')->temporaryUrl($image->path, now()->addMinutes(10)),
        ])->toArray();
    }
}
