@extends('layouts.user')

@section('title', 'Dashboard - City Gymnasium')

@section('content')
<div class="mb-6">
    <h1 class="text-3xl font-bold text-gray-800">
        <i class="fas fa-home text-blue-500 mr-2"></i>Welcome back, {{ auth()->user()->name }}!
    </h1>
    <p class="text-gray-600 mt-1">Here's what's happening with your gym bookings</p>
</div>

<!-- Statistics Cards -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
    <div class="bg-white rounded-lg shadow-lg p-6 hover:shadow-xl transition">
        <div class="flex items-center">
            <div class="bg-blue-100 rounded-full p-3 mr-4">
                <i class="fas fa-clipboard-list text-blue-600 text-2xl"></i>
            </div>
            <div>
                <p class="text-gray-500 text-sm">Total Bookings</p>
                <h3 class="text-2xl font-bold text-gray-800">{{ $totalBookings }}</h3>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-lg p-6 hover:shadow-xl transition">
        <div class="flex items-center">
            <div class="bg-green-100 rounded-full p-3 mr-4">
                <i class="fas fa-check-circle text-green-600 text-2xl"></i>
            </div>
            <div>
                <p class="text-gray-500 text-sm">Confirmed</p>
                <h3 class="text-2xl font-bold text-gray-800">{{ $confirmedBookings }}</h3>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-lg p-6 hover:shadow-xl transition">
        <div class="flex items-center">
            <div class="bg-purple-100 rounded-full p-3 mr-4">
                <i class="fas fa-money-bill-wave text-purple-600 text-2xl"></i>
            </div>
            <div>
                <p class="text-gray-500 text-sm">Total Spent</p>
                <h3 class="text-2xl font-bold text-gray-800">₱{{ number_format($totalSpent, 2) }}</h3>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-lg p-6 hover:shadow-xl transition">
        <div class="flex items-center">
            <div class="bg-yellow-100 rounded-full p-3 mr-4">
                <i class="fas fa-hourglass-half text-yellow-600 text-2xl"></i>
            </div>
            <div>
                <p class="text-gray-500 text-sm">Pending Payment</p>
                <h3 class="text-2xl font-bold text-gray-800">₱{{ number_format($pendingPayments, 2) }}</h3>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <a href="{{ route('user.schedule') }}" class="bg-gradient-to-r from-blue-500 to-blue-600 rounded-lg shadow-lg p-6 text-white hover:from-blue-600 hover:to-blue-700 transition transform hover:scale-105">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-lg font-bold mb-2">Book a Slot</h3>
                <p class="text-blue-100 text-sm">Schedule your gym session</p>
            </div>
            <i class="fas fa-calendar-plus text-4xl opacity-50"></i>
        </div>
    </a>

    <a href="{{ route('user.bookings') }}" class="bg-gradient-to-r from-green-500 to-green-600 rounded-lg shadow-lg p-6 text-white hover:from-green-600 hover:to-green-700 transition transform hover:scale-105">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-lg font-bold mb-2">My Bookings</h3>
                <p class="text-green-100 text-sm">View your reservations</p>
            </div>
            <i class="fas fa-list text-4xl opacity-50"></i>
        </div>
    </a>

    <a href="{{ route('user.payments') }}" class="bg-gradient-to-r from-purple-500 to-purple-600 rounded-lg shadow-lg p-6 text-white hover:from-purple-600 hover:to-purple-700 transition transform hover:scale-105">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-lg font-bold mb-2">Payments</h3>
                <p class="text-purple-100 text-sm">Manage your payments</p>
            </div>
            <i class="fas fa-credit-card text-4xl opacity-50"></i>
        </div>
    </a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    
    <!-- Upcoming Bookings (2 columns) -->
    <div class="lg:col-span-2">
        <div class="bg-white rounded-lg shadow-lg p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-xl font-bold text-gray-800">
                    <i class="fas fa-calendar-alt text-blue-500 mr-2"></i>Upcoming Bookings
                </h3>
                <a href="{{ route('user.bookings') }}" class="text-blue-600 hover:text-blue-700 text-sm font-semibold">View All →</a>
            </div>

            @forelse($upcomingBookings as $booking)
            <div class="border border-gray-200 rounded-lg p-4 mb-3 hover:border-blue-500 hover:shadow-md transition">
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <div class="flex items-center gap-2 mb-2">
                            <span class="text-sm font-bold text-gray-800">{{ $booking->booking_number }}</span>
                            @if($booking->status === 'confirmed')
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                    <i class="fas fa-check-circle mr-1"></i>Confirmed
                                </span>
                            @else
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                    <i class="fas fa-clock mr-1"></i>Pending
                                </span>
                            @endif
                        </div>
                        <div class="space-y-1">
                            <p class="text-sm text-gray-600">
                                <i class="fas fa-calendar text-blue-500 mr-2"></i>
                                {{ $booking->schedule->date->format('l, F d, Y') }}
                            </p>
                            <p class="text-sm text-gray-600">
                                <i class="fas fa-clock text-blue-500 mr-2"></i>
                                {{ $booking->schedule->timeSlot }}
                            </p>
                            <p class="text-sm text-gray-600">
                                <i class="fas fa-users text-blue-500 mr-2"></i>
                                {{ $booking->number_of_slots }} slot(s)
                            </p>
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="text-lg font-bold" style="color: var(--accent-teal)">₱{{ number_format($booking->total_amount, 2) }}</p>
                        @if(!$booking->isPaid)
                            <a href="{{ route('user.payments') }}" class="mt-2 inline-block px-3 py-1 bg-green-600 text-white text-xs rounded-lg hover:bg-green-700 transition">
                                Pay Now
                            </a>
                        @endif
                    </div>
                </div>
            </div>
            @empty
            <div class="text-center py-8 text-gray-500">
                <i class="fas fa-calendar-times text-5xl mb-3 text-gray-300"></i>
                <p class="font-semibold">No upcoming bookings</p>
                <p class="text-sm mt-1">Book a slot to get started!</p>
                <a href="{{ route('user.schedule') }}" class="inline-block mt-4 px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                    <i class="fas fa-plus mr-2"></i>Book Now
                </a>
            </div>
            @endforelse
        </div>

        <!-- Recent Payments -->
        <div class="bg-white rounded-lg shadow-lg p-6 mt-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-xl font-bold text-gray-800">
                    <i class="fas fa-receipt text-green-500 mr-2"></i>Recent Payments
                </h3>
                <a href="{{ route('user.payments') }}" class="text-blue-600 hover:text-blue-700 text-sm font-semibold">View All →</a>
            </div>

            <div class="space-y-3">
                @forelse($recentPayments as $payment)
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 rounded-full flex items-center justify-center
                            {{ $payment->status === 'completed' ? 'bg-green-100' : ($payment->status === 'pending' ? 'bg-yellow-100' : 'bg-red-100') }}">
                            <i class="fas 
                                {{ $payment->status === 'completed' ? 'fa-check text-green-600' : ($payment->status === 'pending' ? 'fa-clock text-yellow-600' : 'fa-times text-red-600') }}">
                            </i>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-gray-800">{{ $payment->payment_number }}</p>
                            <p class="text-xs text-gray-500">{{ $payment->created_at->format('M d, Y') }}</p>
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="font-bold text-gray-800">₱{{ number_format($payment->amount, 2) }}</p>
                        <span class="text-xs px-2 py-1 rounded-full
                            {{ $payment->status === 'completed' ? 'bg-green-100 text-green-800' : ($payment->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') }}">
                            {{ ucfirst($payment->status) }}
                        </span>
                    </div>
                </div>
                @empty
                <div class="text-center py-6 text-gray-500">
                    <i class="fas fa-wallet text-4xl mb-2 text-gray-300"></i>
                    <p class="text-sm">No payment history yet</p>
                </div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Right Column: Notifications & Quick Info -->
    <div class="lg:col-span-1">
        <!-- Notifications -->
        <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-xl font-bold text-gray-800">
                    <i class="fas fa-bell text-yellow-500 mr-2"></i>Notifications
                    @if($unreadNotifications > 0)
                        <span class="ml-2 px-2 py-1 bg-red-500 text-white text-xs rounded-full">{{ $unreadNotifications }}</span>
                    @endif
                </h3>
                <a href="{{ route('user.notifications') }}" class="text-blue-600 hover:text-blue-700 text-sm font-semibold">View All →</a>
            </div>

            <div class="space-y-3">
                @forelse($notifications->take(4) as $notification)
                <div class="p-3 border-l-4 rounded 
                    {{ $notification->type === 'booking_confirmed' ? 'border-green-500 bg-green-50' : 
                       ($notification->type === 'payment_received' ? 'border-blue-500 bg-blue-50' : 'border-gray-500 bg-gray-50') }}">
                    <p class="text-sm font-semibold text-gray-800">{{ $notification->title }}</p>
                    <p class="text-xs text-gray-600 mt-1">{{ Str::limit($notification->message, 60) }}</p>
                    <p class="text-xs text-gray-400 mt-1">{{ $notification->created_at->diffForHumans() }}</p>
                </div>
                @empty
                <div class="text-center py-6 text-gray-500">
                    <i class="fas fa-bell-slash text-4xl mb-2 text-gray-300"></i>
                    <p class="text-sm">No notifications</p>
                </div>
                @endforelse
            </div>
        </div>
         <!-- Gym Location Map -->
        <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
            <h3 class="text-xl font-bold text-gray-800 mb-4">
                <i class="fas fa-map-marker-alt text-red-500 mr-2"></i>Gym Location
            </h3>

            <div
                id="gym-map"
                class="w-full rounded-lg border border-gray-200"
                style="height: 260px;"
            ></div>

            <p class="mt-3 text-xs text-gray-500">
                Surigao City Gymnasium · Borromeo St, Surigao City
            </p>

            <a href="https://www.google.com/maps/search/?api=1&query=9.79118,125.4936"
               target="_blank"
               class="mt-2 inline-flex items-center text-xs text-blue-600 hover:text-blue-700">
                <i class="fas fa-external-link-alt mr-1"></i>Open in Google Maps
            </a>
        </div>
    

        <!-- Member Profile Card -->
        <div class="bg-white rounded-lg shadow-lg p-6 mt-6">
            <div class="text-center">
                <div class="w-20 h-20 mx-auto rounded-full bg-gradient-to-r from-blue-500 to-purple-500 flex items-center justify-center mb-3">
                    <span class="text-3xl font-bold text-white">{{ strtoupper(substr(auth()->user()->name, 0, 2)) }}</span>
                </div>
                <h4 class="font-bold text-gray-800">{{ auth()->user()->name }}</h4>
                <p class="text-sm text-gray-500">{{ auth()->user()->email }}</p>
                <p class="text-xs text-gray-400 mt-1">Member since {{ auth()->user()->created_at->format('M Y') }}</p>
                <a href="{{ route('user.profile') }}" class="mt-4 inline-block px-4 py-2 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700 transition">
                    <i class="fas fa-user-edit mr-2"></i>Edit Profile
                </a>
            </div>
        </div>
    </div>
</div>

@endsection

@push('styles')
    <link
        rel="stylesheet"
        href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
        integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY="
        crossorigin=""
    />
@endpush

@push('scripts')
    <script
        src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
        integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo="
        crossorigin=""
    ></script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const el = document.getElementById('gym-map');
            if (!el || typeof L === 'undefined') {
                return;
            }

            // Surigao City Gymnasium coordinates
            const gymLat = 9.79118;
            const gymLng = 125.4936;

            const map = L.map('gym-map').setView([gymLat, gymLng], 18);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; OpenStreetMap contributors'
            }).addTo(map);

            L.marker([gymLat, gymLng])
                .addTo(map)
                .bindPopup('Surigao City Gymnasium')
                .openPopup();
        });
    </script>
@endpush
