<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RoadmapFollow extends Model
{
    protected $fillable = [
        'roadmap_id',
        'user_id',
    ];
}
