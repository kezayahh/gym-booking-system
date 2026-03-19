@extends('layouts.auth')

@section('title', 'Register - City Gymnasium')

@section('content')
<div class="bg-white rounded-lg shadow-2xl overflow-hidden">
    <div class="bg-gradient-to-r from-purple-500 to-pink-500 p-6 text-center">
        <i class="fas fa-user-plus text-white text-5xl mb-3"></i>
        <h2 class="text-2xl font-bold text-white">Create Account</h2>
        <p class="text-purple-100 mt-2">Join City Gymnasium today!</p>
    </div>
    
    <form method="POST" action="{{ route('register.submit') }}" class="p-8">
        @csrf
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <div>
                <label for="name" class="block text-gray-700 font-semibold mb-2">
                    <i class="fas fa-user text-purple-500 mr-2"></i>Full Name *
                </label>
                <input 
                    type="text" 
                    id="name" 
                    name="name" 
                    value="{{ old('name') }}"
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 @error('name') border-red-500 @enderror" 
                    placeholder="John Doe"
                    required
                >
                @error('name')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>
            
            <div>
                <label for="phone" class="block text-gray-700 font-semibold mb-2">
                    <i class="fas fa-phone text-purple-500 mr-2"></i>Phone Number *
                </label>
                <input 
                    type="tel" 
                    id="phone" 
                    name="phone" 
                    value="{{ old('phone') }}"
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 @error('phone') border-red-500 @enderror" 
                    placeholder="09123456789"
                    required
                >
                @error('phone')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>
        </div>
        
        <div class="mb-4">
            <label for="email" class="block text-gray-700 font-semibold mb-2">
                <i class="fas fa-envelope text-purple-500 mr-2"></i>Email Address *
            </label>
            <input 
                type="email" 
                id="email" 
                name="email" 
                value="{{ old('email') }}"
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 @error('email') border-red-500 @enderror" 
                placeholder="your@email.com"
                required
            >
            @error('email')
                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>
        
        <div class="mb-4">
            <label for="address" class="block text-gray-700 font-semibold mb-2">
                <i class="fas fa-map-marker-alt text-purple-500 mr-2"></i>Address
            </label>
            <textarea 
                id="address" 
                name="address" 
                rows="2"
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 @error('address') border-red-500 @enderror" 
                placeholder="Your complete address"
            >{{ old('address') }}</textarea>
            @error('address')
                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <div>
                <label for="date_of_birth" class="block text-gray-700 font-semibold mb-2">
                    <i class="fas fa-calendar text-purple-500 mr-2"></i>Date of Birth
                </label>
                <input 
                    type="date" 
                    id="date_of_birth" 
                    name="date_of_birth" 
                    value="{{ old('date_of_birth') }}"
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 @error('date_of_birth') border-red-500 @enderror"
                    max="{{ date('Y-m-d') }}"
                >
                @error('date_of_birth')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>
            
            <div>
                <label for="gender" class="block text-gray-700 font-semibold mb-2">
                    <i class="fas fa-venus-mars text-purple-500 mr-2"></i>Gender
                </label>
                <select 
                    id="gender" 
                    name="gender" 
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 @error('gender') border-red-500 @enderror"
                >
                    <option value="">Select Gender</option>
                    <option value="male" {{ old('gender') == 'male' ? 'selected' : '' }}>Male</option>
                    <option value="female" {{ old('gender') == 'female' ? 'selected' : '' }}>Female</option>
                    <option value="other" {{ old('gender') == 'other' ? 'selected' : '' }}>Other</option>
                </select>
                @error('gender')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
            <div>
                <label for="password" class="block text-gray-700 font-semibold mb-2">
                    <i class="fas fa-lock text-purple-500 mr-2"></i>Password *
                </label>
                <input 
                    type="password" 
                    id="password" 
                    name="password" 
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 @error('password') border-red-500 @enderror" 
                    placeholder="Minimum 6 characters"
                    required
                >
                @error('password')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>
            
            <div>
                <label for="password_confirmation" class="block text-gray-700 font-semibold mb-2">
                    <i class="fas fa-lock text-purple-500 mr-2"></i>Confirm Password *
                </label>
                <input 
                    type="password" 
                    id="password_confirmation" 
                    name="password_confirmation" 
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500" 
                    placeholder="Re-enter password"
                    required
                >
            </div>
        </div>
        
        <button 
            type="submit" 
            class="w-full bg-gradient-to-r from-purple-500 to-pink-500 text-white font-bold py-3 px-4 rounded-lg hover:from-purple-600 hover:to-pink-600 transition duration-300 transform hover:scale-105 shadow-lg"
        >
            <i class="fas fa-user-plus mr-2"></i>Create Account
        </button>
        
        <div class="mt-6 text-center">
            <p class="text-gray-600 mb-2">Already have an account?</p>
            <a href="{{ route('login') }}" class="text-purple-600 hover:text-purple-800 font-semibold">
                <i class="fas fa-sign-in-alt mr-1"></i>Login Here
            </a>
        </div>
    </form>
</div>
@endsection