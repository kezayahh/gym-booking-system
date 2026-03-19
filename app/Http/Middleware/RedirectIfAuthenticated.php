<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfAuthenticated
{
    /**
     * Handle an incoming request.
     *
     * @param  string[]  ...$guards
     */
    public function handle(Request $request, Closure $next, string ...$guards): Response
    {
        if (Auth::check()) {
            $user = Auth::user();

            // On admin login page: only redirect if already logged in as admin
            if ($request->is('admin/login')) {
                if ($user->role === 'admin') {
                    return redirect()->route('admin.dashboard');
                }
                return $next($request);
            }

            // On user login/register: allow access even if authenticated
            if ($request->is('user/login') || $request->is('user/register')) {
                return $next($request);
            }

            // Other admin/* routes: redirect by role
            if ($request->is('admin') || $request->is('admin/*')) {
                if ($user->role === 'admin') {
                    return redirect()->route('admin.dashboard');
                }
                return redirect()->route('user.dashboard');
            }

            // Other user/* routes: redirect by role
            if ($request->is('user') || $request->is('user/*')) {
                if ($user->role === 'user') {
                    return redirect()->route('user.dashboard');
                }
                return redirect()->route('admin.dashboard');
            }

            if ($user->role === 'admin') {
                return redirect()->route('admin.dashboard');
            }

            return redirect()->route('user.dashboard');
        }

        return $next($request);
    }
}