<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WantedAd extends Model
{
    /** @use HasFactory<\Database\Factories\WantedAdFactory> */
    use HasFactory;

    protected $guarded = [];

    public const CATEGORIES = [
        'foo',
        'bar',
    ];

    public function user() : \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }

     public function scopeNotBlocked($query)
     {
         $authUser = auth()->user();
         return $query->whereDoesntHave('user', function ($query) use ($authUser) {
             $query->whereIn('id', $authUser->blocks->pluck('id'))
                 ->orWhereIn('id', $authUser->blockedBy->pluck('id'));
         });
     }
}
