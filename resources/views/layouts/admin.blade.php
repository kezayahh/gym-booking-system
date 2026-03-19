<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <title>@yield('title', 'City Gymnasium - Admin Dashboard')</title>
    
    {{-- Tailwind CSS --}}
    <link href="{{ asset('css/tailwind.min.css') }}" rel="stylesheet">
    
    {{-- Font Awesome --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />

    {{-- Alpine.js --}}
    <script src="{{ asset('js/alpine.min.js') }}" defer></script>
    
    {{-- Custom Color Scheme --}}
    <style>
        :root {
            --primary-dark: #0b3c3d;
            --primary-hover: #144b4d;
            --primary-light: #003941;
            --accent-teal: #00b5b0;
        }
    </style>
    
    {{-- Page-specific CSS --}}
    @stack('styles')
</head>
<body class="bg-gray-100 font-sans antialiased" x-data="{ sidebarOpen: true }">
    
    <div class="flex h-screen overflow-hidden">
        
        {{-- Sidebar --}}
        @include('partials.admin.sidebar')
        
        {{-- Main Content Area --}}
        <div class="flex-1 flex flex-col overflow-hidden">
            
            {{-- Top Navigation Bar --}}
            @include('partials.admin.navbar')
            
            {{-- Main Content --}}
            <main class="flex-1 overflow-y-auto bg-gray-100 p-6">
                
                {{-- Breadcrumbs (optional) --}}
                @hasSection('breadcrumbs')
                    <nav class="mb-4">
                        @yield('breadcrumbs')
                    </nav>
                @endif
                
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
                
                {{-- Page Content --}}
                @yield('content')
                
            </main>
            
            {{-- Footer (optional) --}}
            @include('partials.admin.footer')
            
        </div>
    </div>
    
    {{-- Page-specific JavaScript --}}
    @stack('scripts')
    
</body>
</html>