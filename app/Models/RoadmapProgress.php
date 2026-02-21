<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RoadmapProgress extends Model
{
    protected $fillable = [
        'roadmap_step_id',
        'user_id',
        'completed_at',
    ];

    protected $casts = [
        'completed_at' => 'datetime',
    ];
}
