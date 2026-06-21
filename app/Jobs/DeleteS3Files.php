<?php

namespace App\Jobs;

use App\Services\ImageService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class DeleteS3Files implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly array $paths) {}

    public function handle(): void
    {
        foreach ($this->paths as $path) {
            ImageService::delete($path);
        }
    }
}
