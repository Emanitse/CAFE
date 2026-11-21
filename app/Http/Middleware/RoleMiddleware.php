<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$roles
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        // Check if user is authenticated via session
        $accountId = session('accountID');
        $userRole = session('role');

        if (!$accountId || !$userRole) {
            return redirect()->route('login')->with('error', 'Please log in to access this page.');
        }

        // If no roles specified, just require authentication
        if (empty($roles)) {
            return $next($request);
        }

        // Admin has access to everything
        if ($userRole === 'admin') {
            return $next($request);
        }

        // Check if user's role matches any of the allowed roles
        if (!in_array($userRole, $roles, true)) {
            return redirect()->route('home')->with('error', 'You do not have permission to access this page.');
        }

        return $next($request);
    }
}