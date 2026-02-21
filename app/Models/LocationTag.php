<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LocationTag extends Model
{
    protected $fillable = [
        'user_id',
        'title',
        'note',
        'lat',
        'lng',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
