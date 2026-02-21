<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SkillEndorsement extends Model
{
    protected $fillable = [
        'endorser_id',
        'endorsee_id',
        'skill',
    ];

    public function endorser()
    {
        return $this->belongsTo(User::class, 'endorser_id');
    }

    public function endorsee()
    {
        return $this->belongsTo(User::class, 'endorsee_id');
    }
}
