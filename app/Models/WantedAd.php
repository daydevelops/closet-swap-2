<?php

namespace App\Models;

use App\Traits\FilterBlocked;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WantedAd extends Model
{
    /** @use HasFactory<\Database\Factories\WantedAdFactory> */
    use HasFactory;
    use FilterBlocked;

    protected $guarded = [];

    public const CATEGORIES = [
        'foo',
        'bar',
    ];

    public function user() : \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }

}
