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
      
        $accountId = session('accountID');
        $userRole = session('role');

        if (!$accountId || !$userRole) {
            return redirect()->route('login')->with('error', 'Please log in to access this page.');
        }

      
        if (empty($roles)) {
            return $next($request);
        }

       
        if (in_array($userRole, ['admin', 'superadmin'], true)){
            return $next($request);
        }

      
        if (!in_array($userRole, $roles, true)) {
            return redirect()->route('home')->with('error', 'You do not have permission to access this page.');
        }

        return $next($request);
    }
}