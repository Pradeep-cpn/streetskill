<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RoadmapStep extends Model
{
    protected $fillable = [
        'roadmap_id',
        'title',
        'sort_order',
    ];
}
