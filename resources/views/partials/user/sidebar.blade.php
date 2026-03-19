{{-- File: resources/views/partials/user/sidebar.blade.php --}}

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
                <p class="text-xs text-blue-200">Member Portal</p>

            </div>
        </div>

        {{-- User Profile Section --}}
        <div class="bg-white bg-opacity-10 rounded-lg p-4 mb-6">
            <div class="flex items-center space-x-3">
                <div class="w-12 h-12 rounded-full bg-gradient-to-r from-blue-400 to-purple-500 flex items-center justify-center">
                    <i class="fas fa-user text-white text-lg"></i>
                </div>
                <div class="flex-1">
                    <p class="text-white font-semibold text-sm">{{ Auth::user()->name }}</p>
                    <p class="text-blue-200 text-xs">Member</p>
                </div>
            </div>
        </div>

        {{-- Navigation Menu --}}
        <nav class="space-y-2">
            {{-- Dashboard --}}
            <a href="{{ route('user.dashboard') }}"
               class="flex items-center space-x-3 px-4 py-3 rounded-lg transition-colors duration-200
                      {{ request()->routeIs('user.dashboard') ? 'bg-white bg-opacity-20 text-white' : 'text-blue-100 hover:bg-white hover:bg-opacity-10 hover:text-white' }}">
                <i class="fas fa-home w-5"></i>
                <span class="font-medium">Dashboard</span>
            </a>

            {{-- Schedule --}}
            <a href="{{ route('user.schedule') }}"
               class="flex items-center space-x-3 px-4 py-3 rounded-lg transition-colors duration-200
                      {{ request()->routeIs('user.schedule') ? 'bg-white bg-opacity-20 text-white' : 'text-blue-100 hover:bg-white hover:bg-opacity-10 hover:text-white' }}">
                <i class="fas fa-calendar-alt w-5"></i>
                <span class="font-medium">Schedule</span>
            </a>

            {{-- My Bookings --}}
            <a href="{{ route('user.bookings') }}"
               class="flex items-center space-x-3 px-4 py-3 rounded-lg transition-colors duration-200
                      {{ request()->routeIs('user.bookings') ? 'bg-white bg-opacity-20 text-white' : 'text-blue-100 hover:bg-white hover:bg-opacity-10 hover:text-white' }}">
                <i class="fas fa-clipboard-list w-5"></i>
                <span class="font-medium">My Bookings</span>
            </a>

            {{-- Payments --}}
            <a href="{{ route('user.payments') }}"
               class="flex items-center space-x-3 px-4 py-3 rounded-lg transition-colors duration-200
                      {{ request()->routeIs('user.payments') ? 'bg-white bg-opacity-20 text-white' : 'text-blue-100 hover:bg-white hover:bg-opacity-10 hover:text-white' }}">
                <i class="fas fa-credit-card w-5"></i>
                <span class="font-medium">Payments</span>
            </a>

            {{-- Notifications --}}
            <a href="{{ route('user.notifications') }}"
               class="flex items-center space-x-3 px-4 py-3 rounded-lg transition-colors duration-200
                      {{ request()->routeIs('user.notifications') ? 'bg-white bg-opacity-20 text-white' : 'text-blue-100 hover:bg-white hover:bg-opacity-10 hover:text-white' }}">
                <i class="fas fa-bell w-5"></i>
                <span class="font-medium">Notifications</span>
                @if(Auth::user()->notifications()->unread()->count() > 0)
                    <span class="ml-auto bg-red-500 text-white text-xs rounded-full px-2 py-1">
                        {{ Auth::user()->notifications()->unread()->count() }}
                    </span>
                @endif
            </a>

            {{-- Profile --}}
            <a href="{{ route('user.profile') }}"
               class="flex items-center space-x-3 px-4 py-3 rounded-lg transition-colors duration-200
                      {{ request()->routeIs('user.profile') ? 'bg-white bg-opacity-20 text-white' : 'text-blue-100 hover:bg-white hover:bg-opacity-10 hover:text-white' }}">
                <i class="fas fa-user-cog w-5"></i>
                <span class="font-medium">Profile</span>
            </a>

            {{-- Divider --}}
            <div class="border-t border-white border-opacity-20 my-4"></div>

            {{-- Logout --}}
            <button 
                @click="logoutModal = true"
                class="w-full flex items-center space-x-3 px-4 py-3 rounded-lg transition-colors duration-200 text-red-300 hover:bg-red-500 hover:bg-opacity-20 hover:text-red-200">
                <i class="fas fa-sign-out-alt w-5"></i>
                <span class="font-medium">Logout</span>
            </button>
        </nav>
    </div>
</aside>