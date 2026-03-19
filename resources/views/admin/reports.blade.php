{{-- File: resources/views/admin/reports.blade.php --}}
@extends('layouts.admin')
@section('title', 'Reports & Analytics - City Gymnasium')

@section('content')
<div class="mb-6">
    <h1 class="text-3xl font-bold text-gray-800">
        <i class="fas fa-chart-line text-blue-500 mr-2"></i>Reports & Analytics
    </h1>
    <p class="text-gray-600 mt-1">View comprehensive reports and analytics</p>
</div>

{{-- Date Range Filter --}}
<div class="bg-white rounded-lg shadow-lg p-6 mb-6">
    <div class="flex flex-wrap items-end gap-4">
        <div class="flex-1 min-w-[200px]">
            <label class="block text-sm font-semibold text-gray-700 mb-2">
                <i class="fas fa-calendar text-gray-400 mr-1"></i>Start Date
            </label>
            <input type="date" id="start_date" value="{{ $startDate ?? now()->subDays(30)->format('Y-m-d') }}"
                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
        </div>
        
        <div class="flex-1 min-w-[200px]">
            <label class="block text-sm font-semibold text-gray-700 mb-2">
                <i class="fas fa-calendar text-gray-400 mr-1"></i>End Date
            </label>
            <input type="date" id="end_date" value="{{ $endDate ?? now()->format('Y-m-d') }}"
                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
        </div>
        
        <button onclick="applyDateFilter()" 
                class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-semibold">
            <i class="fas fa-filter mr-2"></i>Apply Filter
        </button>
        
        <button onclick="exportReport('pdf')" 
                class="px-6 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition font-semibold">
            <i class="fas fa-file-pdf mr-2"></i>Export PDF
        </button>
        
        <button onclick="exportReport('excel')" 
                class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition font-semibold">
            <i class="fas fa-file-excel mr-2"></i>Export Excel
        </button>
    </div>
</div>

{{-- Overview Statistics --}}
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <div class="bg-white rounded-lg shadow-lg p-6">
        <div class="flex items-center">
            <div class="bg-blue-100 rounded-full p-3 mr-4">
                <i class="fas fa-calendar-check text-blue-600 text-2xl"></i>
            </div>
            <div>
                <p class="text-gray-500 text-sm">Total Bookings</p>
                <h3 class="text-2xl font-bold text-gray-800">{{ $totalBookings ?? 0 }}</h3>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-lg p-6">
        <div class="flex items-center">
            <div class="bg-green-100 rounded-full p-3 mr-4">
                <i class="fas fa-dollar-sign text-green-600 text-2xl"></i>
            </div>
            <div>
                <p class="text-gray-500 text-sm">Total Revenue</p>
                <h3 class="text-2xl font-bold text-gray-800">₱{{ number_format($totalRevenue ?? 0, 2) }}</h3>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-lg p-6">
        <div class="flex items-center">
            <div class="bg-purple-100 rounded-full p-3 mr-4">
                <i class="fas fa-users text-purple-600 text-2xl"></i>
            </div>
            <div>
                <p class="text-gray-500 text-sm">Total Users</p>
                <h3 class="text-2xl font-bold text-gray-800">{{ $totalUsers ?? 0 }}</h3>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-lg p-6">
        <div class="flex items-center">
            <div class="bg-yellow-100 rounded-full p-3 mr-4">
                <i class="fas fa-undo text-yellow-600 text-2xl"></i>
            </div>
            <div>
                <p class="text-gray-500 text-sm">Total Refunds</p>
                <h3 class="text-2xl font-bold text-gray-800">₱{{ number_format($totalRefunds ?? 0, 2) }}</h3>
            </div>
        </div>
    </div>
</div>

{{-- Charts Section --}}
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    {{-- Revenue Chart --}}
<div class="bg-white rounded-lg shadow-lg p-6">
    <h3 class="text-xl font-bold text-gray-800 mb-4">
        <i class="fas fa-chart-line text-blue-500 mr-2"></i>Revenue Trend
    </h3>
    <div class="relative h-64"> {{-- fixed height container --}}
        <canvas id="revenueChart"></canvas> {{-- remove height attribute --}}
    </div>
</div>


{{-- Booking Status Chart --}}
<div class="bg-white rounded-lg shadow-lg p-6">
    <h3 class="text-xl font-bold text-gray-800 mb-4">
        <i class="fas fa-chart-pie text-green-500 mr-2"></i>Booking Status Distribution
    </h3>
    <div class="relative h-64">
        <canvas id="bookingStatusChart"></canvas> {{-- removed height attr --}}
    </div>
</div>


{{-- Payment Methods Chart --}}
<div class="bg-white rounded-lg shadow-lg p-6">
    <h3 class="text-xl font-bold text-gray-800 mb-4">
        <i class="fas fa-credit-card text-purple-500 mr-2"></i>Payment Methods
    </h3>
    <div class="relative h-64">
        <canvas id="paymentMethodsChart"></canvas>
    </div>
</div>


    {{-- Monthly Comparison Chart --}}
<div class="bg-white rounded-lg shadow-lg p-6">
    <h3 class="text-xl font-bold text-gray-800 mb-4">
        <i class="fas fa-calendar text-orange-500 mr-2"></i>Monthly Revenue Comparison
    </h3>
    <div class="relative h-64">
        <canvas id="monthlyComparisonChart"></canvas>
    </div>
</div>

{{-- Top Users Table --}}
<div class="bg-white rounded-lg shadow-lg overflow-hidden mb-8">
    <div class="p-6 border-b border-gray-200">
        <h3 class="text-xl font-bold text-gray-800">
            <i class="fas fa-trophy text-yellow-500 mr-2"></i>Top Users by Bookings
        </h3>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rank</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Bookings</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Spent</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($topUsers ?? [] as $index => $user)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="text-2xl">
                            @if($index === 0) 🥇
                            @elseif($index === 1) 🥈
                            @elseif($index === 2) 🥉
                            @else {{ $index + 1 }}
                            @endif
                        </span>
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex items-center">
                            <img src="{{ $user->profile_picture_url }}" alt="{{ $user->name }}" 
                                 class="w-10 h-10 rounded-full mr-3">
                            <div>
                                <div class="text-sm font-medium text-gray-900">{{ $user->name }}</div>
                                <div class="text-xs text-gray-500">{{ $user->email }}</div>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="text-sm font-bold text-gray-900">{{ $user->bookings_count }}</span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="text-sm font-bold text-green-600">₱{{ number_format($user->total_spent, 2) }}</span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full 
                                     {{ $user->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                            {{ ucfirst($user->status) }}
                        </span>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                        <i class="fas fa-inbox text-6xl mb-4 text-gray-300"></i>
                        <p>No data available for the selected period</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Recent Activity Table --}}
<div class="bg-white rounded-lg shadow-lg overflow-hidden">
    <div class="p-6 border-b border-gray-200">
        <h3 class="text-xl font-bold text-gray-800">
            <i class="fas fa-history text-blue-500 mr-2"></i>Recent Activity
        </h3>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date & Time</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Activity</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($recentActivities ?? [] as $activity)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        {{ $activity->created_at->format('M d, Y h:i A') }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        {{ $activity->user->name ?? 'System' }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full 
                                     {{ $activity->action === 'created' ? 'bg-green-100 text-green-800' : 
                                        ($activity->action === 'updated' ? 'bg-blue-100 text-blue-800' : 
                                        ($activity->action === 'deleted' ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-800')) }}">
                            {{ ucfirst($activity->action) }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-900">
                        {{ $activity->description }}
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="px-6 py-12 text-center text-gray-500">
                        <i class="fas fa-inbox text-6xl mb-4 text-gray-300"></i>
                        <p>No recent activity</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

@push('scripts')
{{-- Chart.js Library --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

{{-- jsPDF and Excel Export Libraries --}}
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>

<script>
// Chart Data from Backend
const revenueData = @json($revenueChartData ?? []);
const bookingStatusData = @json($bookingStatusData ?? []);
const paymentMethodsData = @json($paymentMethodsData ?? []);
const monthlyComparisonData = @json($monthlyComparisonData ?? []);

// Revenue Trend Chart
const revenueCtx = document.getElementById('revenueChart').getContext('2d');
new Chart(revenueCtx, {
    type: 'line',
    data: {
        labels: revenueData.labels || ['No Data'],
        datasets: [{
            label: 'Revenue (₱)',
            data: revenueData.values || [0],
            borderColor: 'rgb(59, 130, 246)',
            backgroundColor: 'rgba(59, 130, 246, 0.1)',
            tension: 0.4,
            fill: true
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: true },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return '₱' + context.parsed.y.toLocaleString();
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

// Booking Status Chart
const bookingStatusCtx = document.getElementById('bookingStatusChart').getContext('2d');
new Chart(bookingStatusCtx, {
    type: 'doughnut',
    data: {
        labels: bookingStatusData.labels || ['No Data'],
        datasets: [{
            data: bookingStatusData.values || [1],
            backgroundColor: [
                'rgb(234, 179, 8)',  // Pending - Yellow
                'rgb(34, 197, 94)',  // Confirmed - Green
                'rgb(59, 130, 246)', // Completed - Blue
                'rgb(239, 68, 68)',  // Cancelled - Red
                'rgb(156, 163, 175)' // No Show - Gray
            ]
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { position: 'bottom' }
        }
    }
});

// Payment Methods Chart
const paymentMethodsCtx = document.getElementById('paymentMethodsChart').getContext('2d');
new Chart(paymentMethodsCtx, {
    type: 'bar',
    data: {
        labels: paymentMethodsData.labels || ['No Data'],
        datasets: [{
            label: 'Payments',
            data: paymentMethodsData.values || [0],
            backgroundColor: [
                'rgb(34, 197, 94)',
                'rgb(59, 130, 246)',
                'rgb(168, 85, 247)',
                'rgb(234, 179, 8)',
                'rgb(239, 68, 68)'
            ]
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false }
        },
        scales: {
            y: { beginAtZero: true }
        }
    }
});

// Monthly Comparison Chart
const monthlyComparisonCtx = document.getElementById('monthlyComparisonChart').getContext('2d');
new Chart(monthlyComparisonCtx, {
    type: 'bar',
    data: {
        labels: monthlyComparisonData.labels || ['No Data'],
        datasets: [{
            label: 'Revenue (₱)',
            data: monthlyComparisonData.values || [0],
            backgroundColor: 'rgb(59, 130, 246)',
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return '₱' + context.parsed.y.toLocaleString();
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

// Apply Date Filter
function applyDateFilter() {
    const startDate = document.getElementById('start_date').value;
    const endDate = document.getElementById('end_date').value;
    
    window.location.href = `/admin/reports?start=${startDate}&end=${endDate}`;
}

// Export Functions
function exportReport(type) {
    if (type === 'pdf') {
        exportPDF();
    } else if (type === 'excel') {
        exportExcel();
    }
}

function exportPDF() {
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF();
    
    doc.text('City Gymnasium - Reports', 14, 15);
    doc.setFontSize(10);
    doc.text(`Generated: ${new Date().toLocaleDateString()}`, 14, 22);
    
    // Add statistics
    doc.autoTable({
        startY: 30,
        head: [['Metric', 'Value']],
        body: [
            ['Total Bookings', '{{ $totalBookings ?? 0 }}'],
            ['Total Revenue', '₱{{ number_format($totalRevenue ?? 0, 2) }}'],
            ['Total Users', '{{ $totalUsers ?? 0 }}'],
            ['Total Refunds', '₱{{ number_format($totalRefunds ?? 0, 2) }}']
        ]
    });
    
    doc.save('gym-report.pdf');
}

function exportExcel() {
    const data = [
        ['City Gymnasium - Reports'],
        ['Generated:', new Date().toLocaleDateString()],
        [],
        ['Metric', 'Value'],
        ['Total Bookings', '{{ $totalBookings ?? 0 }}'],
        ['Total Revenue', '₱{{ number_format($totalRevenue ?? 0, 2) }}'],
        ['Total Users', '{{ $totalUsers ?? 0 }}'],
        ['Total Refunds', '₱{{ number_format($totalRefunds ?? 0, 2) }}']
    ];
    
    const ws = XLSX.utils.aoa_to_sheet(data);
    const wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, 'Report');
    XLSX.writeFile(wb, 'gym-report.xlsx');
}
</script>
@endpush