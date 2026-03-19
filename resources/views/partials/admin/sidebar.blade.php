{{-- File: resources/views/partials/admin/sidebar.blade.php --}}

<aside 
    class="w-64 flex-shrink-0 overflow-y-auto transition-all duration-300"
    :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full md:translate-x-0'"
    style="background-color: var(--primary-dark);"
>
    <div class="p-6">
        {{-- Logo --}}
        <div class="flex justify-center mb-8">
            <div class="flex flex-col items-center text-center">
                
                <img src="{{ asset('images/logo.png') }}" 
                    alt="Gym" 
                    class="h-32 w-32 mx-auto">

                <h1 class="text-xl font-bold text-white mt-3">City Gymnasium</h1>
                <p class="text-xs text-blue-200">Admin Panel</p>

            </div>
        </div>

        {{-- Admin Profile Section --}}
        <div class="bg-white bg-opacity-10 rounded-lg p-4 mb-6">
            <div class="flex items-center space-x-3">
                <div class="w-12 h-12 rounded-full bg-gradient-to-r from-yellow-400 to-orange-500 flex items-center justify-center">
                    <i class="fas fa-user-shield text-white text-lg"></i>
                </div>
                <div class="flex-1">
                    <p class="text-white font-semibold text-sm">{{ Auth::user()->name }}</p>
                    <p class="text-blue-200 text-xs">Administrator</p>
                </div>
            </div>
        </div>

        {{-- Navigation Menu --}}
        <nav class="space-y-2">
            {{-- Dashboard --}}
            <a href="{{ route('admin.dashboard') }}"
               class="flex items-center space-x-3 px-4 py-3 rounded-lg transition-colors duration-200
                      {{ request()->routeIs('admin.dashboard') ? 'bg-white bg-opacity-20 text-white' : 'text-blue-100 hover:bg-white hover:bg-opacity-10 hover:text-white' }}">
                <i class="fas fa-tachometer-alt w-5"></i>
                <span class="font-medium">Dashboard</span>
            </a>

            {{-- User Management --}}
            <a href="{{ route('admin.users') }}"
               class="flex items-center space-x-3 px-4 py-3 rounded-lg transition-colors duration-200
                      {{ request()->routeIs('admin.users') ? 'bg-white bg-opacity-20 text-white' : 'text-blue-100 hover:bg-white hover:bg-opacity-10 hover:text-white' }}">
                <i class="fas fa-users w-5"></i>
                <span class="font-medium">Users</span>
            </a>

            {{-- Schedule Management --}}
            <a href="{{ route('admin.schedules') }}"
               class="flex items-center space-x-3 px-4 py-3 rounded-lg transition-colors duration-200
                      {{ request()->routeIs('admin.schedules') ? 'bg-white bg-opacity-20 text-white' : 'text-blue-100 hover:bg-white hover:bg-opacity-10 hover:text-white' }}">
                <i class="fas fa-calendar-alt w-5"></i>
                <span class="font-medium">Schedules</span>
            </a>

            {{-- Booking Management --}}
            <a href="{{ route('admin.bookings') }}"
               class="flex items-center space-x-3 px-4 py-3 rounded-lg transition-colors duration-200
                      {{ request()->routeIs('admin.bookings') ? 'bg-white bg-opacity-20 text-white' : 'text-blue-100 hover:bg-white hover:bg-opacity-10 hover:text-white' }}">
                <i class="fas fa-calendar-check w-5"></i>
                <span class="font-medium">Bookings</span>
            </a>

            {{-- Payment Management --}}
            <a href="{{ route('admin.payments') }}"
               class="flex items-center space-x-3 px-4 py-3 rounded-lg transition-colors duration-200
                      {{ request()->routeIs('admin.payments') ? 'bg-white bg-opacity-20 text-white' : 'text-blue-100 hover:bg-white hover:bg-opacity-10 hover:text-white' }}">
                <i class="fas fa-money-bill-wave w-5"></i>
                <span class="font-medium">Payments</span>
            </a>

            {{-- Refund Management --}}
            <a href="{{ route('admin.refunds') }}"
               class="flex items-center space-x-3 px-4 py-3 rounded-lg transition-colors duration-200
                      {{ request()->routeIs('admin.refunds') ? 'bg-white bg-opacity-20 text-white' : 'text-blue-100 hover:bg-white hover:bg-opacity-10 hover:text-white' }}">
                <i class="fas fa-undo-alt w-5"></i>
                <span class="font-medium">Refunds</span>
            </a>

            {{-- Reports --}}
            <a href="{{ route('admin.reports') }}"
               class="flex items-center space-x-3 px-4 py-3 rounded-lg transition-colors duration-200
                      {{ request()->routeIs('admin.reports') ? 'bg-white bg-opacity-20 text-white' : 'text-blue-100 hover:bg-white hover:bg-opacity-10 hover:text-white' }}">
                <i class="fas fa-chart-bar w-5"></i>
                <span class="font-medium">Reports</span>
            </a>

            {{-- Profile --}}
            <a href="{{ route('admin.profile') }}"
               class="flex items-center space-x-3 px-4 py-3 rounded-lg transition-colors duration-200
                      {{ request()->routeIs('admin.profile') ? 'bg-white bg-opacity-20 text-white' : 'text-blue-100 hover:bg-white hover:bg-opacity-10 hover:text-white' }}">
                <i class="fas fa-user-cog w-5"></i>
                <span class="font-medium">Profile</span>
            </a>

            {{-- Divider --}}
            <div class="border-t border-white border-opacity-20 my-4"></div>

            {{-- Logout --}}
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button 
                    type="submit"
                    class="w-full flex items-center space-x-3 px-4 py-3 rounded-lg transition-colors duration-200 text-red-300 hover:bg-red-500 hover:bg-opacity-20 hover:text-red-200">
                    <i class="fas fa-sign-out-alt w-5"></i>
                    <span class="font-medium">Logout</span>
                </button>
            </form>
        </nav>
    </div>
</aside>