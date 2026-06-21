<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class Like extends Pivot
{
    protected $table = 'likes';

    public $timestamps = true;
}
