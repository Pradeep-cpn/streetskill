<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'slug',
        'email',
        'password',
        'city',
        'bio',
        'headline',
        'skills_offered',
        'skills_wanted',
        'availability_slots',
        'rating',
        'portfolio_links',
        'website_url',
        'linkedin_url',
        'instagram_url',
        'youtube_url',
        'verified_badge',
        'verification_requested_at',
        'last_active_at',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'availability_slots' => 'array',
            'rating' => 'float',
            'is_admin' => 'boolean',
            'portfolio_links' => 'array',
            'verification_requested_at' => 'datetime',
            'hide_tags_until' => 'datetime',
            'last_active_at' => 'datetime',
        ];
    }

    public function profile()
    {
        return $this->hasOne(Profile::class);
    }

    public function activityLogs()
    {
        return $this->hasMany(ActivityLog::class);
    }

    public function connectionsRequested()
    {
        return $this->hasMany(Connection::class, 'requester_id');
    }

    public function connectionsReceived()
    {
        return $this->hasMany(Connection::class, 'addressee_id');
    }

    public function locationTags()
    {
        return $this->hasMany(LocationTag::class);
    }

    public static function generateUniqueSlug(string $name): string
    {
        $base = Str::slug($name);
        $slug = $base !== '' ? $base : 'user';
        $counter = 1;

        while (self::query()->where('slug', $slug)->exists()) {
            $slug = $base . '-' . $counter;
            $counter++;
        }

        return $slug;
    }
}
