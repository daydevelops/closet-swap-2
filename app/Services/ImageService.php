<?php

namespace App\Services;
use Illuminate\Support\Facades\Storage;

class ImageService
{
    public function upload($image) {
        if ($image) {
            $path = $image->store('images', 's3');
            return Storage::disk('s3')->url($path);
        }
        return null;
    }
}
