<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Profile extends Model
{
    protected $fillable = [
        'user_id',
        'skill_tags',
        'price_min',
        'price_max',
        'availability_status',
    ];

    protected $casts = [
        'skill_tags' => 'array',
        'price_min' => 'integer',
        'price_max' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
