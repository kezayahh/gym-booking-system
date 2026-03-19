<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    // Show User Login Form
    public function showLoginForm()
    {
        return view('app');
    }

    // Show User Register Form
    public function showRegisterForm()
    {
        return view('app');
    }

    // Handle User Login
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

            // Check if user role is 'user'
            if ($user->role !== 'user') {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid credentials. Please use admin login.',
                    ], 403);
                }

                return back()->with('error', 'Invalid credentials. Please use admin login.')->withInput();
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

            if (function_exists('activity')) {
                activity()
                    ->causedBy($user)
                    ->log('User logged in');
            }

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Welcome back, ' . $user->name . '!',
                    'redirect' => '/user/dashboard',
                    'user' => $user,
                ]);
            }

            return redirect()->route('user.dashboard')
                ->with('success', 'Welcome back, ' . $user->name . '!');
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid email or password.',
            ], 401);
        }

        return back()->with('error', 'Invalid email or password.')->withInput();
    }

    // Handle User Registration
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'phone' => 'required|string|max:20',
            'password' => 'required|string|min:6|confirmed',
            'address' => 'nullable|string',
            'date_of_birth' => 'nullable|date|before:today',
            'gender' => 'nullable|in:male,female,other',
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

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            'address' => $request->address,
            'date_of_birth' => $request->date_of_birth,
            'gender' => $request->gender,
            'role' => 'user',
            'status' => 'active',
        ]);

        Auth::login($user);
        $request->session()->regenerate();

        if (function_exists('activity')) {
            activity()
                ->causedBy($user)
                ->log('New user registered');
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Registration successful! Welcome to City Gymnasium.',
                'redirect' => '/user/dashboard',
                'user' => $user,
            ]);
        }

        return redirect()->route('user.dashboard')
            ->with('success', 'Registration successful! Welcome to City Gymnasium.');
    }

    // Handle Logout
    public function logout(Request $request)
    {
        $user = Auth::user();

        if ($user && function_exists('activity')) {
            activity()
                ->causedBy($user)
                ->log('User logged out');
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

        return redirect('/user/login')->with('success', 'You have been logged out successfully.');
    }
}