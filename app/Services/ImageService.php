<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
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
            'signed_url' => self::signedUrl($image->path),
        ])->toArray();
    }

    /**
     * Generate a 10-minute signed URL for a single S3 path.
     * External URLs are returned as-is.
     */
    public static function signedUrl(string $path): string
    {
        if (str_starts_with($path, 'http')) return $path;

        return Cache::remember('signed_url:' . $path, 540, function () use ($path) {
            return Storage::disk('s3')->temporaryUrl($path, now()->addMinutes(10));
        });
    }

    /**
     * Delete a file from S3. No-ops on external URLs.
     */
    public static function delete(string $path): void
    {
        if (!str_starts_with($path, 'http')) {
            Cache::forget('signed_url:' . $path);
            Storage::disk('s3')->delete($path);
        }
    }
}
