<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class EnforceSingleSession
{
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check() && Schema::hasColumn('users', 'current_session_id')) {
            $user = $request->user();
            $sessionId = $request->session()->getId();

            if ($user->current_session_id && $user->current_session_id !== $sessionId) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect('/')
                    ->with('error', 'You were signed out because your account was used on another device.');
            }
        }

        return $next($request);
    }
}
