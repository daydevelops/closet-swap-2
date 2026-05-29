<?php

namespace App\Services;
use Illuminate\Support\Facades\Storage;

class ImageService
{
    public function upload($image) {
        if ($image) {
            return $image->store('images', 's3');
        }
        return null;
    }
}
