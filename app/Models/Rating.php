<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Rating extends Model
{
    protected $fillable = [
        'from_user_id',
        'to_user_id',
        'swap_request_id',
        'skill',
        'rating',
        'review',
        'verified',
        'weight',
    ];

    protected $casts = [
        'verified' => 'boolean',
        'weight' => 'float',
    ];
}
