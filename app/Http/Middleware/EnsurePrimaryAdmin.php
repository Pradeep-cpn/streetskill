<?php

namespace App\Http\Middleware;

use App\Support\AdminPolicy;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePrimaryAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(AdminPolicy::isPrimaryAdmin($request->user()), 403);

        return $next($request);
    }
}
