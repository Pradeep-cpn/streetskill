<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Roadmap extends Model
{
    protected $fillable = [
        'user_id',
        'title',
        'description',
    ];

    public function steps()
    {
        return $this->hasMany(RoadmapStep::class)->orderBy('sort_order');
    }
}
