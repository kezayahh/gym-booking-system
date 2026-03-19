<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <title>@yield('title', 'City Gymnasium — Login')</title>
    
    {{-- Tailwind CSS --}}
    <link href="{{ asset('css/tailwind.min.css') }}" rel="stylesheet">
    
    {{-- Google Fonts --}}
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@600;700;800;900&family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    
    {{-- Alpine.js --}}
    <script src="{{ asset('js/alpine.min.js') }}" defer></script>
    
    {{-- Page-specific CSS --}}
    @stack('styles')
    
    <style>
        body {
            font-family: 'Roboto', sans-serif;
        }
        h1, h2, h3 {
            font-family: 'Poppins', sans-serif;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-blue-500 via-purple-500 to-pink-500 min-h-screen flex flex-col">
    
    {{-- Header --}}
    <header class="bg-white shadow-md">
        <div class="container mx-auto px-4 py-4">
            <h1 class="text-2xl md:text-3xl font-bold text-gray-800 text-center">
                <i class="fas fa-dumbbell text-blue-600 mr-2"></i>
                City Gymnasium Booking System
            </h1>
        </div>
    </header>
    
    {{-- Main Content --}}
    <main class="flex-1 flex items-center justify-center px-4 py-8">
        <div class="w-full max-w-md">
            
            {{-- Flash Messages --}}
            @if(session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline">{{ session('success') }}</span>
                </div>
            @endif
            
            @if(session('error'))
                <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline">{{ session('error') }}</span>
                </div>
            @endif
            
            @if($errors->any())
                <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                    <ul class="list-disc list-inside">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            
            {{-- Page Content --}}
            @yield('content')
            
        </div>
    </main>
    
    {{-- Footer --}}
    <footer class="bg-white shadow-md mt-8">
        <div class="container mx-auto px-4 py-4 text-center text-gray-600 text-sm">
            <p>&copy; {{ date('Y') }} City Gymnasium. All rights reserved.</p>
        </div>
    </footer>
    
    {{-- Page-specific JavaScript --}}
    @stack('scripts')
    
</body>
</html>