<?php

namespace App\Http\Middleware;

use App\Enums\UserStatus;
use Closure;
use Illuminate\Http\Request;

class EnsureActiveUser
{
    public function handle(Request $request, Closure $next)
    {
        abort_unless($request->user()?->status === UserStatus::Active, 403, 'Your account is not active. Please contact your administrator.');

        return $next($request);
    }
}
