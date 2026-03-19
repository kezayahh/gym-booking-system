{{-- File: resources/views/admin/dashboard.blade.php --}}
@extends('layouts.admin')
@section('title', 'Admin Dashboard - City Gymnasium')

@section('content')
<div class="mb-6">
    <h1 class="text-3xl font-bold text-gray-800">
        <i class="fas fa-tachometer-alt text-blue-500 mr-2"></i>Dashboard
    </h1>
    <p class="text-gray-600 mt-1">Welcome back, {{ Auth::user()->name }}! Here's what's happening today.</p>
</div>

{{-- Overview Statistics Cards --}}
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    {{-- Total Users --}}
    <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-lg shadow-lg p-6 text-white transform hover:scale-105 transition">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-blue-100 text-sm font-semibold mb-1">Total Users</p>
                <h3 class="text-4xl font-bold">{{ $totalUsers }}</h3>
                <p class="text-blue-100 text-xs mt-2">
                    <i class="fas fa-users mr-1"></i>Registered members
                </p>
            </div>
            <div class="bg-white bg-opacity-20 rounded-full p-4">
                <i class="fas fa-users text-4xl"></i>
            </div>
        </div>
    </div>

    {{-- Total Bookings --}}
    <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-lg shadow-lg p-6 text-white transform hover:scale-105 transition">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-green-100 text-sm font-semibold mb-1">Total Bookings</p>
                <h3 class="text-4xl font-bold">{{ $totalBookings }}</h3>
                <p class="text-green-100 text-xs mt-2">
                    <i class="fas fa-calendar-plus mr-1"></i>{{ $todayBookings }} today
                </p>
            </div>
            <div class="bg-white bg-opacity-20 rounded-full p-4">
                <i class="fas fa-calendar-check text-4xl"></i>
            </div>
        </div>
    </div>

    {{-- Total Revenue --}}
    <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-lg shadow-lg p-6 text-white transform hover:scale-105 transition">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-purple-100 text-sm font-semibold mb-1">Total Revenue</p>
                <h3 class="text-4xl font-bold">₱{{ number_format($totalRevenue, 0) }}</h3>
                <p class="text-purple-100 text-xs mt-2">
                    <i class="fas fa-money-bill-wave mr-1"></i>₱{{ number_format($todayRevenue, 0) }} today
                </p>
            </div>
            <div class="bg-white bg-opacity-20 rounded-full p-4">
                <i class="fas fa-dollar-sign text-4xl"></i>
            </div>
        </div>
    </div>

    {{-- Pending Refunds --}}
    <div class="bg-gradient-to-br from-yellow-500 to-yellow-600 rounded-lg shadow-lg p-6 text-white transform hover:scale-105 transition">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-yellow-100 text-sm font-semibold mb-1">Pending Refunds</p>
                <h3 class="text-4xl font-bold">{{ $pendingRefunds }}</h3>
                <p class="text-yellow-100 text-xs mt-2">
                    <i class="fas fa-exclamation-circle mr-1"></i>Needs attention
                </p>
            </div>
            <div class="bg-white bg-opacity-20 rounded-full p-4">
                <i class="fas fa-undo text-4xl"></i>
            </div>
        </div>
    </div>
</div>

{{-- Charts Section --}}
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    {{-- Monthly Revenue Chart --}}
    <div class="bg-white rounded-lg shadow-lg p-6">
        <h3 class="text-xl font-bold text-gray-800 mb-4">
            <i class="fas fa-chart-line text-blue-500 mr-2"></i>Monthly Revenue (Last 6 Months)
        </h3>
        <div style="position: relative; height: 300px;">
            <canvas id="monthlyRevenueChart"></canvas>
        </div>
    </div>

    {{-- Booking Status Chart --}}
    <div class="bg-white rounded-lg shadow-lg p-6">
        <h3 class="text-xl font-bold text-gray-800 mb-4">
            <i class="fas fa-chart-pie text-green-500 mr-2"></i>Booking Status Distribution
        </h3>
        <div style="position: relative; height: 300px;">
            <canvas id="bookingStatusChart"></canvas>
        </div>
    </div>
</div>

{{-- Upcoming Schedules & Booking Stats --}}
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
    {{-- Upcoming Schedules --}}
    <div class="bg-white rounded-lg shadow-lg p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-bold text-gray-800">
                <i class="fas fa-calendar-alt text-purple-500 mr-2"></i>Upcoming Schedules
            </h3>
        </div>
        <div class="space-y-3">
            @forelse($upcomingSchedules as $schedule)
            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
                <div>
                    <p class="text-sm font-semibold text-gray-800">
                        {{ $schedule->date->format('M d, Y') }}
                    </p>
                    <p class="text-xs text-gray-600">{{ $schedule->time_slot }}</p>
                </div>
                <div class="text-right">
                    <p class="text-xs text-gray-600">{{ $schedule->available_slots }}/{{ $schedule->total_capacity }}</p>
                    <span class="text-xs font-bold text-green-600">₱{{ number_format($schedule->price_per_slot, 0) }}</span>
                </div>
            </div>
            @empty
            <p class="text-center text-gray-500 py-4">No upcoming schedules</p>
            @endforelse
        </div>
    </div>

    {{-- Booking Statistics --}}
    <div class="bg-white rounded-lg shadow-lg p-6 col-span-2">
        <h3 class="text-lg font-bold text-gray-800 mb-4">
            <i class="fas fa-chart-bar text-blue-500 mr-2"></i>Booking Statistics
        </h3>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="text-center p-4 bg-yellow-50 rounded-lg">
                <div class="text-3xl font-bold text-yellow-600">{{ $bookingStats['pending'] ?? 0 }}</div>
                <div class="text-sm text-gray-600 mt-1">Pending</div>
            </div>
            <div class="text-center p-4 bg-green-50 rounded-lg">
                <div class="text-3xl font-bold text-green-600">{{ $bookingStats['confirmed'] ?? 0 }}</div>
                <div class="text-sm text-gray-600 mt-1">Confirmed</div>
            </div>
            <div class="text-center p-4 bg-blue-50 rounded-lg">
                <div class="text-3xl font-bold text-blue-600">{{ $bookingStats['completed'] ?? 0 }}</div>
                <div class="text-sm text-gray-600 mt-1">Completed</div>
            </div>
            <div class="text-center p-4 bg-red-50 rounded-lg">
                <div class="text-3xl font-bold text-red-600">{{ $bookingStats['cancelled'] ?? 0 }}</div>
                <div class="text-sm text-gray-600 mt-1">Cancelled</div>
            </div>
        </div>
    </div>
</div>

{{-- Recent Bookings & Recent Activity --}}
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    {{-- Recent Bookings --}}
    <div class="bg-white rounded-lg shadow-lg overflow-hidden">
        <div class="p-6 border-b border-gray-200 flex items-center justify-between">
            <h3 class="text-xl font-bold text-gray-800">
                <i class="fas fa-calendar-alt text-blue-500 mr-2"></i>Recent Bookings
            </h3>
            <a href="{{ route('admin.bookings') }}" class="text-blue-600 hover:text-blue-700 text-sm font-semibold">
                View All →
            </a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Booking #</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">User</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($recentBookings as $booking)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                            {{ $booking->booking_number }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ $booking->user->name }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                            {{ $booking->schedule->date->format('M d, Y') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($booking->status === 'pending')
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                    Pending
                                </span>
                            @elseif($booking->status === 'confirmed')
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                    Confirmed
                                </span>
                            @elseif($booking->status === 'completed')
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                                    Completed
                                </span>
                            @else
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">
                                    {{ ucfirst($booking->status) }}
                                </span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="px-6 py-12 text-center text-gray-500">
                            <i class="fas fa-inbox text-4xl mb-2 text-gray-300"></i>
                            <p>No recent bookings</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Recent Activity --}}
    <div class="bg-white rounded-lg shadow-lg overflow-hidden">
        <div class="p-6 border-b border-gray-200">
            <h3 class="text-xl font-bold text-gray-800">
                <i class="fas fa-history text-green-500 mr-2"></i>Recent Activity
            </h3>
        </div>
        <div class="overflow-y-auto max-h-96">
            <div class="divide-y divide-gray-200">
                @forelse($recentActivities as $activity)
                <div class="p-4 hover:bg-gray-50 transition">
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            @if($activity->action === 'created')
                                <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                                    <i class="fas fa-plus text-green-600 text-xs"></i>
                                </div>
                            @elseif($activity->action === 'updated')
                                <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                                    <i class="fas fa-edit text-blue-600 text-xs"></i>
                                </div>
                            @elseif($activity->action === 'deleted')
                                <div class="w-8 h-8 bg-red-100 rounded-full flex items-center justify-center">
                                    <i class="fas fa-trash text-red-600 text-xs"></i>
                                </div>
                            @else
                                <div class="w-8 h-8 bg-gray-100 rounded-full flex items-center justify-center">
                                    <i class="fas fa-info text-gray-600 text-xs"></i>
                                </div>
                            @endif
                        </div>
                        <div class="ml-3 flex-1">
                            <p class="text-sm font-medium text-gray-900">
                                {{ $activity->user->name ?? 'System' }}
                            </p>
                            <p class="text-xs text-gray-600 mt-1">
                                {{ $activity->description }}
                            </p>
                            <p class="text-xs text-gray-400 mt-1">
                                <i class="far fa-clock mr-1"></i>{{ $activity->created_at->diffForHumans() }}
                            </p>
                        </div>
                    </div>
                </div>
                @empty
                <div class="p-12 text-center text-gray-500">
                    <i class="fas fa-inbox text-4xl mb-2 text-gray-300"></i>
                    <p>No recent activity</p>
                </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
{{-- Chart.js Library --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<script>
// Monthly Revenue Chart Data (FIXED)
const monthlyRevenueData = @json($monthlyRevenue);

// Sort data by month (oldest to newest) - THIS WAS THE BUG!
const sortedData = [...monthlyRevenueData].sort((a, b) => {
    return new Date(a.month) - new Date(b.month);
});

const revenueLabels = sortedData.map(item => {
    const [year, month] = item.month.split('-');
    const date = new Date(year, month - 1);
    return date.toLocaleDateString('en-US', { month: 'short', year: 'numeric' });
});
const revenueValues = sortedData.map(item => parseFloat(item.total));

console.log('Revenue Labels:', revenueLabels); // Debug
console.log('Revenue Values:', revenueValues); // Debug

// Monthly Revenue Chart
const revenueCtx = document.getElementById('monthlyRevenueChart').getContext('2d');
new Chart(revenueCtx, {
    type: 'line',
    data: {
        labels: revenueLabels,
        datasets: [{
            label: 'Revenue (₱)',
            data: revenueValues,
            borderColor: 'rgb(59, 130, 246)',
            backgroundColor: 'rgba(59, 130, 246, 0.1)',
            tension: 0.4,
            fill: true,
            borderWidth: 2
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: { display: false },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return '₱' + context.parsed.y.toLocaleString('en-US', {minimumFractionDigits: 2});
                    }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return '₱' + value.toLocaleString();
                    }
                }
            }
        }
    }
});

// Booking Status Chart Data
const bookingStats = @json($bookingStats);
const statusLabels = Object.keys(bookingStats).map(status => status.charAt(0).toUpperCase() + status.slice(1));
const statusValues = Object.values(bookingStats);

// Booking Status Chart
const bookingStatusCtx = document.getElementById('bookingStatusChart').getContext('2d');
new Chart(bookingStatusCtx, {
    type: 'doughnut',
    data: {
        labels: statusLabels,
        datasets: [{
            data: statusValues,
            backgroundColor: [
                'rgb(234, 179, 8)',  // Pending - Yellow
                'rgb(34, 197, 94)',  // Confirmed - Green
                'rgb(59, 130, 246)', // Completed - Blue
                'rgb(239, 68, 68)',  // Cancelled - Red
                'rgb(156, 163, 175)' // No Show - Gray
            ],
            borderWidth: 0
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: { 
                position: 'bottom',
                labels: {
                    padding: 15,
                    usePointStyle: true
                }
            }
        }
    }
});
</script>
@endpush