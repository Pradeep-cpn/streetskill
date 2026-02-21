<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChallengeVote extends Model
{
    protected $fillable = [
        'submission_id',
        'user_id',
    ];
}
