{{-- Top Navigation Bar --}}
<header class="bg-white shadow-sm border-b border-gray-200">
    <div class="flex items-center justify-between px-6 py-4">
        
        {{-- Left: Hamburger Menu (Mobile) & Page Title --}}
        <div class="flex items-center space-x-4">
            <button @click="sidebarOpen = !sidebarOpen" 
                    class="text-gray-500 hover:text-gray-700 focus:outline-none lg:hidden">
                <i class="fas fa-bars text-xl"></i>
            </button>
            
           
        </div>
        
        {{-- Right: Search & Quick Actions --}}
        <div class="flex items-center space-x-4">
            
            
            {{-- Notifications Bell --}}
            <div x-data="{ open: false }" class="relative">
                <button @click="open = !open" 
                        class="relative text-gray-500 hover:text-gray-700 focus:outline-none">
                    <i class="fas fa-bell text-xl"></i>
                    {{-- Notification Badge --}}
                    <span class="absolute -top-1 -right-1 flex h-5 w-5 items-center justify-center rounded-full bg-red-500 text-xs text-white">3</span>
                </button>
                
                {{-- Notification Dropdown --}}
                <div x-show="open" 
                     @click.away="open = false"
                     x-cloak
                     style="display: none;"
                     class="absolute right-0 mt-2 w-80 bg-white rounded-lg shadow-lg border border-gray-200 z-50">
                    <div class="p-4 border-b border-gray-200">
                        <h3 class="font-semibold text-gray-800">Notifications</h3>
                    </div>
                    <div class="max-h-64 overflow-y-auto">
                        <a href="{{ route('user.notifications') }}" class="block px-4 py-3 hover:bg-gray-50 border-b border-gray-100">
                            <div class="flex items-start space-x-3">
                                <div class="flex-shrink-0">
                                    <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                                        <i class="fas fa-check text-green-600"></i>
                                    </div>
                                </div>
                                <div class="flex-1">
                                    <p class="text-sm text-gray-800 font-medium">Booking Confirmed</p>
                                    <p class="text-xs text-gray-500 mt-1">Your booking for tomorrow at 9:00 AM is confirmed</p>
                                    <p class="text-xs text-gray-400 mt-1">2 hours ago</p>
                                </div>
                            </div>
                        </a>
                        <a href="{{ route('user.notifications') }}" class="block px-4 py-3 hover:bg-gray-50 border-b border-gray-100">
                            <div class="flex items-start space-x-3">
                                <div class="flex-shrink-0">
                                    <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                                        <i class="fas fa-info text-blue-600"></i>
                                    </div>
                                </div>
                                <div class="flex-1">
                                    <p class="text-sm text-gray-800 font-medium">Payment Received</p>
                                    <p class="text-xs text-gray-500 mt-1">Payment of $50 processed successfully</p>
                                    <p class="text-xs text-gray-400 mt-1">5 hours ago</p>
                                </div>
                            </div>
                        </a>
                    </div>
                    <div class="p-3 text-center border-t border-gray-200">
                        <a href="{{ route('user.notifications') }}" class="text-sm text-blue-600 hover:text-blue-700">View all notifications</a>
                    </div>
                </div>
            </div>
            
            {{-- Profile Link --}}
            <a href="{{ route('user.profile') }}" 
               class="flex items-center space-x-2 text-gray-700 hover:text-blue-600 transition-colors">
                <img src="https://ui-avatars.com/api/?name={{ auth()->user()->name ?? 'User' }}&background=3b82f6&color=fff" 
                     alt="Profile" 
                     class="w-10 h-10 rounded-full border-2 border-gray-300">
                <span class="hidden md:block text-sm font-medium">Profile</span>
            </a>
            
        </div>
    </div>
</header>