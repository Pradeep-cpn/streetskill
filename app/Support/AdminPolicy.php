<?php

namespace App\Support;

use App\Models\User;

class AdminPolicy
{
    public static function isPrimaryAdmin(?User $user): bool
    {
        if (!$user || !$user->is_admin) {
            return false;
        }

        $configuredEmail = trim((string) config('streetskill.admin_email'));

        if ($configuredEmail !== '') {
            return strcasecmp($user->email, $configuredEmail) === 0;
        }

        $firstAdminId = User::query()
            ->where('is_admin', true)
            ->orderBy('id')
            ->value('id');

        return $firstAdminId !== null && (int) $user->id === (int) $firstAdminId;
    }
}
