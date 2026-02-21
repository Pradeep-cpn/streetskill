<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    protected $fillable = [
        'title',
        'description',
        'created_by',
    ];

    public function members()
    {
        return $this->hasMany(RoomMember::class);
    }
}
