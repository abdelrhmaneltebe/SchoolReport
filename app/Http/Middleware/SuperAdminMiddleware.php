<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SuperAdminMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check()) {
            return redirect()->route('login')->with('error', 'Please sign in to continue.');
        }

        if (!auth()->user()->isSuperAdmin()) {
            return redirect()->route('dashboard')
                ->with('error', 'Access Restricted: Super Administrator privileges required.');
        }

        return $next($request);
    }
}
