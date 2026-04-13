<?php

// File: app/Http/Middleware/Authenticate.php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function redirectTo(Request $request): ?string
    {
        if ($request->expectsJson()) {
            return null;
        }

        // Check if trying to access admin routes
        if (
            $request->is('admin') ||
            $request->is('admin/*') ||
            $request->is('api/admin') ||
            $request->is('api/admin/*')
        ) {
            return route('admin.login');
        }

        // Default to user login
        return route('user.login');
    }
}