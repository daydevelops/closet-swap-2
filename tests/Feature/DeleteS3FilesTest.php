<?php

use App\Jobs\DeleteS3Files;
use Illuminate\Support\Facades\Storage;

test('DeleteS3Files job deletes the given paths from S3', function () {
    Storage::fake('s3');

    $paths = [
        'images/test-1.jpg',
        'images/test-2.jpg',
    ];

    foreach ($paths as $path) {
        Storage::disk('s3')->put($path, 'fake');
    }

    (new DeleteS3Files($paths))->handle();

    foreach ($paths as $path) {
        Storage::disk('s3')->assertMissing($path);
    }
});

test('DeleteS3Files job skips external URLs', function () {
    Storage::fake('s3');

    // Should not throw even with an http:// URL in the list
    (new DeleteS3Files(['https://example.com/photo.jpg']))->handle();

    Storage::disk('s3')->assertDirectoryEmpty('images');
});
