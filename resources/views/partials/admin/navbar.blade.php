{{-- Top Navigation Bar --}}
<header class="bg-gray-300 shadow-sm">
    <div class="flex items-center justify-between px-6 py-4">
        
        {{-- Left: Hamburger Menu (Mobile) --}}
        <div class="flex items-center space-x-4">
            <button @click="sidebarOpen = !sidebarOpen" 
                    class="text-gray-700 hover:text-gray-900 focus:outline-none lg:hidden">
                <i class="fas fa-bars text-xl"></i>
            </button>
            
            
        </div>
        
        {{-- Right: Search, Notifications, Profile --}}
        <div class="flex items-center space-x-4">
            
            
            
            {{-- Notifications --}}
            <div x-data="{ open: false }" class="relative">
                <button @click="open = !open" 
                        class="relative text-gray-700 hover:text-gray-900 focus:outline-none">
                    <i class="fas fa-bell text-xl"></i>
                    {{-- Notification Badge --}}
                    <span class="absolute top-0 right-0 block h-2 w-2 rounded-full bg-red-500 ring-2 ring-white"></span>
                </button>
                
                {{-- Notification Dropdown --}}
                <div x-show="open" 
                     @click.away="open = false"
                     x-cloak
                     style="display: none;"
                     class="absolute right-0 mt-2 w-80 bg-white rounded-lg shadow-lg border border-gray-200 z-50">
                    <div class="p-4 border-b border-gray-200" style="background-color: #0b3c3d;">
                        <h3 class="font-semibold text-white">Notifications</h3>
                    </div>
                    <div class="max-h-64 overflow-y-auto">
                        <a href="#" class="block px-4 py-3 hover:bg-gray-50 border-b border-gray-100">
                            <p class="text-sm text-gray-800 font-medium">New booking received</p>
                            <p class="text-xs text-gray-500 mt-1">5 minutes ago</p>
                        </a>
                        <a href="#" class="block px-4 py-3 hover:bg-gray-50 border-b border-gray-100">
                            <p class="text-sm text-gray-800 font-medium">Payment confirmed</p>
                            <p class="text-xs text-gray-500 mt-1">1 hour ago</p>
                        </a>
                    </div>
                    <div class="p-3 text-center border-t border-gray-200">
                        <a href="#" class="text-sm hover:underline" style="color: #0b3c3d;">View all notifications</a>
                    </div>
                </div>
            </div>
            
            {{-- Profile Dropdown --}}
            <div x-data="{ open: false }" class="relative">
                <button @click="open = !open" 
                        class="flex items-center space-x-2 focus:outline-none">
                    <img src="https://ui-avatars.com/api/?name=Admin&background=0b3c3d&color=fff" 
                         alt="Profile" 
                         class="w-10 h-10 rounded-full border-2 border-gray-400">
                    <span class="hidden md:block text-sm font-medium text-gray-700">Admin</span>
                    <i class="fas fa-chevron-down text-xs text-gray-600"></i>
                </button>
                
                {{-- Profile Dropdown Menu --}}
                <div x-show="open" 
                     @click.away="open = false"
                     x-cloak
                     style="display: none;"
                     class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-200 z-50">
                    <div class="p-3 border-b border-gray-200">
                        <p class="text-sm font-medium text-gray-800">Admin User</p>
                        <p class="text-xs text-gray-500">admin@citygym.com</p>
                    </div>
                    <div class="py-2">
                        <a href="{{ route('admin.profile') }}" 
                           class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                            <i class="fas fa-user mr-2"></i> My Profile
                        </a>
        
                    </div>
                    <div class="border-t border-gray-200">
                        {{-- <form action="{{ route('logout') }}" method="POST">
                            @csrf
                            <button type="submit" 
                                    class="block w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-gray-100">
                                <i class="fas fa-sign-out-alt mr-2"></i> Logout
                            </button>
                        </form> --}}
                    </div>
                </div>
            </div>
            
        </div>
    </div>
</header>