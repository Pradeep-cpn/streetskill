<?php

namespace App\Support;

use App\Models\Profile;
use App\Models\User;

class ProfileMetrics
{
    public static function completion(User $user, ?Profile $profile = null): array
    {
        $profile = $profile ?? $user->profile;
        $checks = [
            ['label' => 'City added', 'done' => !empty($user->city)],
            ['label' => 'Bio added', 'done' => !empty($user->bio)],
            ['label' => 'Headline added', 'done' => !empty($user->headline)],
            ['label' => 'Teaching skills added', 'done' => !empty($user->skills_offered)],
            ['label' => 'Learning goals added', 'done' => !empty($user->skills_wanted)],
            ['label' => 'Availability slots selected', 'done' => !empty($user->availability_slots)],
            ['label' => 'Skill tags added', 'done' => !empty($profile?->skill_tags)],
            ['label' => 'Price range set', 'done' => (bool) ($profile?->price_min || $profile?->price_max)],
            ['label' => 'Availability status set', 'done' => !empty($profile?->availability_status)],
        ];

        $doneCount = collect($checks)->where('done', true)->count();
        $completion = (int) round(($doneCount / max(count($checks), 1)) * 100);

        return [
            'completion' => $completion,
            'checklist' => $checks,
        ];
    }
}
