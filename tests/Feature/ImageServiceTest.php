<?php

use App\Services\ImageService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Cache::flush();
});

test('signedUrl caches the result in redis', function () {
    Storage::shouldReceive('disk->temporaryUrl')
        ->once()
        ->andReturn('https://s3.example.com/signed?token=abc');

    ImageService::signedUrl('images/photo.jpg');

    expect(Cache::has('signed_url:images/photo.jpg'))->toBeTrue();
});

test('signedUrl returns cached value on second call without hitting s3 again', function () {
    Storage::shouldReceive('disk->temporaryUrl')
        ->once()
        ->andReturn('https://s3.example.com/signed?token=abc');

    $first  = ImageService::signedUrl('images/photo.jpg');
    $second = ImageService::signedUrl('images/photo.jpg');

    expect($first)->toBe($second);
});

test('signedUrl returns external urls as-is without caching', function () {
    Cache::spy();

    $url = ImageService::signedUrl('https://picsum.photos/400/400');

    expect($url)->toBe('https://picsum.photos/400/400');
    Cache::shouldNotHaveReceived('remember');
});

test('delete forgets the signed url cache key', function () {
    Cache::put('signed_url:images/photo.jpg', 'https://s3.example.com/signed?token=abc', 540);

    Storage::shouldReceive('disk->delete')->once();

    ImageService::delete('images/photo.jpg');

    expect(Cache::has('signed_url:images/photo.jpg'))->toBeFalse();
});
