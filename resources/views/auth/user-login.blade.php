@extends('layouts.auth')

@section('title', 'User Login - City Gymnasium')

@section('content')
<div class="bg-white rounded-lg shadow-2xl overflow-hidden">
    <div class="bg-gradient-to-r from-green-500 to-teal-500 p-6 text-center">
        <i class="fas fa-user-circle text-white text-5xl mb-3"></i>
        <h2 class="text-2xl font-bold text-white">Member Login</h2>
        <p class="text-green-100 mt-2">Welcome back! Please login to your account</p>
    </div>
    
    <form method="POST" action="{{ route('login.submit') }}" class="p-8">
        @csrf
        
        <div class="mb-6">
            <label for="email" class="block text-gray-700 font-semibold mb-2">
                <i class="fas fa-envelope text-green-500 mr-2"></i>Email Address
            </label>
            <input 
                type="email" 
                id="email" 
                name="email" 
                value="{{ old('email') }}"
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent @error('email') border-red-500 @enderror" 
                placeholder="Enter your email"
                required
                autofocus
            >
            @error('email')
                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>
        
        <div class="mb-6">
            <label for="password" class="block text-gray-700 font-semibold mb-2">
                <i class="fas fa-lock text-green-500 mr-2"></i>Password
            </label>
            <input 
                type="password" 
                id="password" 
                name="password" 
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent @error('password') border-red-500 @enderror" 
                placeholder="Enter your password"
                required
            >
            @error('password')
                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>
        
        <div class="flex items-center justify-between mb-6">
            <label class="flex items-center">
                <input type="checkbox" name="remember" class="form-checkbox h-4 w-4 text-green-600 rounded">
                <span class="ml-2 text-gray-700 text-sm">Remember me</span>
            </label>
        </div>
        
        <button 
            type="submit" 
            class="w-full bg-gradient-to-r from-green-500 to-teal-500 text-white font-bold py-3 px-4 rounded-lg hover:from-green-600 hover:to-teal-600 transition duration-300 transform hover:scale-105 shadow-lg"
        >
            <i class="fas fa-sign-in-alt mr-2"></i>Login
        </button>
        
        <div class="mt-6 text-center">
            <p class="text-gray-600 mb-3">Don't have an account?</p>
            <a href="{{ route('register') }}" class="text-green-600 hover:text-green-800 font-semibold">
                <i class="fas fa-user-plus mr-1"></i>Create New Account
            </a>
        </div>
        
        <div class="mt-4 text-center">
            <a href="{{ route('admin.login') }}" class="text-blue-600 hover:text-blue-800 text-sm">
                <i class="fas fa-user-shield mr-1"></i>Admin Login
            </a>
        </div>
    </form>
</div>

<div class="mt-4 text-center text-white text-sm bg-black bg-opacity-30 rounded-lg p-3">
    <i class="fas fa-info-circle mr-1"></i>
    <strong>Test User:</strong> user@citygym.com / user123
</div>
@endsection