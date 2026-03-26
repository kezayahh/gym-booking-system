<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Refund;
use App\Models\Schedule;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function dashboardData()
    {
        $user = Auth::user();

        $totalUsers = User::where('role', 'user')->count();
        $totalBookings = Booking::count();
        $totalRevenue = Payment::where('status', 'completed')->sum('amount');
        $pendingRefunds = Refund::where('status', 'pending')->count();

        $todayBookings = Booking::whereDate('created_at', Carbon::today())->count();
        $todayRevenue = Payment::where('status', 'completed')
            ->whereDate('created_at', Carbon::today())
            ->sum('amount');

        $recentBookings = Booking::with(['user', 'schedule'])
            ->latest()
            ->take(10)
            ->get()
            ->map(function ($booking) {
                return [
                    'id' => $booking->id,
                    'booking_number' => $booking->booking_number,
                    'user_name' => $booking->user->name ?? 'N/A',
                    'schedule_date' => $booking->schedule && $booking->schedule->date
                        ? Carbon::parse($booking->schedule->date)->format('M d, Y')
                        : '',
                    'status' => $booking->status,
                ];
            })
            ->values();

        $monthlyRevenue = Payment::where('status', 'completed')
            ->where('created_at', '>=', Carbon::now()->subMonths(6))
            ->select(
                DB::raw('DATE_FORMAT(created_at, "%Y-%m") as month'),
                DB::raw('SUM(amount) as total')
            )
            ->groupBy('month')
            ->orderBy('month', 'ASC')
            ->get()
            ->map(function ($item) {
                return [
                    'month' => $item->month,
                    'month_label' => Carbon::createFromFormat('Y-m', $item->month)->format('M Y'),
                    'total' => (float) $item->total,
                ];
            })
            ->values();

        $bookingStatsRaw = Booking::select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get()
            ->pluck('count', 'status');

        $recentActivities = ActivityLog::with('user')
            ->latest()
            ->take(10)
            ->get()
            ->map(function ($activity) {
                return [
                    'id' => $activity->id,
                    'user_name' => $activity->user->name ?? 'System',
                    'action' => $activity->action,
                    'description' => $activity->description,
                    'time' => $activity->created_at ? $activity->created_at->diffForHumans() : '',
                ];
            })
            ->values();

        $upcomingSchedules = Schedule::where('date', '>=', Carbon::today())
            ->where('status', 'available')
            ->orderBy('date')
            ->orderBy('start_time')
            ->take(5)
            ->get()
            ->map(function ($schedule) {
                return [
                    'id' => $schedule->id,
                    'date' => $schedule->date ? Carbon::parse($schedule->date)->format('M d, Y') : '',
                    'time_slot' => $schedule->time_slot,
                    'available_slots' => $schedule->available_slots,
                    'total_capacity' => $schedule->total_capacity,
                    'price_per_slot' => $schedule->price_per_slot,
                ];
            })
            ->values();

        return response()->json([
            'admin' => [
                'name' => $user?->name ?? 'Admin',
                'email' => $user?->email ?? '',
            ],
            'overview' => [
                'totalUsers' => $totalUsers,
                'totalBookings' => $totalBookings,
                'totalRevenue' => $totalRevenue,
                'pendingRefunds' => $pendingRefunds,
                'todayBookings' => $todayBookings,
                'todayRevenue' => $todayRevenue,
            ],
            'monthlyRevenue' => $monthlyRevenue,
            'bookingStats' => [
                'pending' => $bookingStatsRaw['pending'] ?? 0,
                'confirmed' => $bookingStatsRaw['confirmed'] ?? 0,
                'completed' => $bookingStatsRaw['completed'] ?? 0,
                'cancelled' => $bookingStatsRaw['cancelled'] ?? 0,
                'no_show' => $bookingStatsRaw['no_show'] ?? 0,
            ],
            'recentBookings' => $recentBookings,
            'recentActivities' => $recentActivities,
            'upcomingSchedules' => $upcomingSchedules,
        ]);
    }
}