<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class AdminAuthController extends Controller
{
    // Show Admin Login Form
    public function showLoginForm()
    {
        if (Auth::check() && Auth::user()->role === 'admin') {
            return redirect()->route('admin.dashboard');
        }

        return view('app');
    }

    // Handle Admin Login
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|min:6',
        ]);

        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first(),
                    'errors' => $validator->errors(),
                ], 422);
            }

            return back()->withErrors($validator)->withInput();
        }

        $credentials = $request->only('email', 'password');
        $remember = $request->has('remember');

        if (Auth::attempt($credentials, $remember)) {
            $user = Auth::user();

            // Check if user role is 'admin'
            if ($user->role !== 'admin') {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid admin credentials. Please use user login.',
                    ], 403);
                }

                return back()->with('error', 'Invalid admin credentials. Please use user login.')->withInput();
            }

            // Check if account is active
            if ($user->status !== 'active') {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Your account has been suspended. Please contact support.',
                    ], 403);
                }

                return back()->with('error', 'Your account has been suspended. Please contact support.')->withInput();
            }

            $request->session()->regenerate();

            // Log activity
            if (function_exists('activity')) {
                activity()
                    ->causedBy($user)
                    ->log('Admin logged in');
            }

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Welcome back, Admin!',
                    'redirect' => '/admin/dashboard',
                    'user' => $user,
                ]);
            }

            return redirect()->intended(route('admin.dashboard'))
                ->with('success', 'Welcome back, Admin!');
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid email or password.',
            ], 401);
        }

        return back()->with('error', 'Invalid email or password.')->withInput();
    }

    // Handle Admin Logout
    public function logout(Request $request)
    {
        $user = Auth::user();

        // Log activity
        if ($user && function_exists('activity')) {
            activity()
                ->causedBy($user)
                ->log('Admin logged out');
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'You have been logged out successfully.',
            ]);
        }

        return redirect()->route('admin.login')->with('success', 'You have been logged out successfully.');
    }
}