<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class UpdateLastActive
{
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check()) {
            $user = Auth::user();
            $now = Carbon::now();

            if (!$user->last_active_at || $user->last_active_at->lt($now->copy()->subMinutes(5))) {
                $user->forceFill(['last_active_at' => $now])->save();
            }
        }

        return $next($request);
    }
}
