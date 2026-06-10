<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Donation extends Model
{
    use HasFactory;

    protected $fillable = [
        'stripe_session_id',
        'amount_cents',
        'currency',
        'donor_email',
        'status',
    ];
}
