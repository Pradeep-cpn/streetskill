<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    protected $fillable = [
        'from_user_id',
        'to_user_id',
        'message',
        'read_at',
        'message_type',
        'image_path',
        'image_mime',
        'image_size',
    ];

    protected $casts = [
        'read_at' => 'datetime',
    ];
}
