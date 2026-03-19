@extends('layouts.auth')

@section('title', 'Admin Login - City Gymnasium')

@section('content')
<div class="bg-white rounded-lg shadow-2xl overflow-hidden">
    <div class="bg-gradient-to-r from-blue-600 to-purple-600 p-6 text-center">
        <i class="fas fa-user-shield text-white text-5xl mb-3"></i>
        <h2 class="text-2xl font-bold text-white">Admin Login</h2>
        <p class="text-blue-100 mt-2">Access the administration panel</p>
    </div>
    
    <form method="POST" action="{{ route('admin.login.submit') }}" class="p-8">
        @csrf
        
        <div class="mb-6">
            <label for="email" class="block text-gray-700 font-semibold mb-2">
                <i class="fas fa-envelope text-blue-500 mr-2"></i>Email Address
            </label>
            <input 
                type="email" 
                id="email" 
                name="email" 
                value="{{ old('email') }}"
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('email') border-red-500 @enderror" 
                placeholder="Enter your admin email"
                required
                autofocus
            >
            @error('email')
                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>
        
        <div class="mb-6">
            <label for="password" class="block text-gray-700 font-semibold mb-2">
                <i class="fas fa-lock text-blue-500 mr-2"></i>Password
            </label>
            <input 
                type="password" 
                id="password" 
                name="password" 
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('password') border-red-500 @enderror" 
                placeholder="Enter your password"
                required
            >
            @error('password')
                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>
        
        <div class="flex items-center justify-between mb-6">
            <label class="flex items-center">
                <input type="checkbox" name="remember" class="form-checkbox h-4 w-4 text-blue-600 rounded">
                <span class="ml-2 text-gray-700 text-sm">Remember me</span>
            </label>
        </div>
        
        <button 
            type="submit" 
            class="w-full bg-gradient-to-r from-blue-600 to-purple-600 text-white font-bold py-3 px-4 rounded-lg hover:from-blue-700 hover:to-purple-700 transition duration-300 transform hover:scale-105 shadow-lg"
        >
            <i class="fas fa-sign-in-alt mr-2"></i>Login as Admin
        </button>
        
        <div class="mt-6 text-center">
            <a href="{{ route('login') }}" class="text-blue-600 hover:text-blue-800 text-sm font-semibold">
                <i class="fas fa-arrow-left mr-1"></i>Back to User Login
            </a>
        </div>
    </form>
</div>

<div class="mt-4 text-center text-white text-sm bg-black bg-opacity-30 rounded-lg p-3">
    <i class="fas fa-info-circle mr-1"></i>
    <strong>Test Admin:</strong> admin@citygym.com / admin123
</div>
@endsection