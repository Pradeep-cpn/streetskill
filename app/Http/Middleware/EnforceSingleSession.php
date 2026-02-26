<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class EnforceSingleSession
{
    protected static ?bool $hasCurrentSessionColumn = null;

    public function handle(Request $request, Closure $next)
    {
        if (!Auth::check() || !$this->hasCurrentSessionColumn()) {
            return $next($request);
        }

        $user = $request->user();
        $sessionId = $request->session()->getId();

        // If no session is registered yet, register current one instead of forcing a logout.
        if (empty($user->current_session_id)) {
            $user->forceFill(['current_session_id' => $sessionId])->save();
            return $next($request);
        }

        if ($user->current_session_id !== $sessionId) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect('/')
                ->with('error', 'You were signed out because your account was used on another device.');
        }

        return $next($request);
    }

    protected function hasCurrentSessionColumn(): bool
    {
        if (self::$hasCurrentSessionColumn === null) {
            self::$hasCurrentSessionColumn = Schema::hasColumn('users', 'current_session_id');
        }

        return self::$hasCurrentSessionColumn;
    }
}
