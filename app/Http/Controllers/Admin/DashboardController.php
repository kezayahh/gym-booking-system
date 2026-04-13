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
            ->where('created_at', '>=', Carbon::now()->subMonths(6)->startOfMonth())
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
                    'action' => $activity->action ?? 'info',
                    'description' => $activity->description ?? '',
                    'time' => $activity->created_at ? $activity->created_at->diffForHumans() : '',
                ];
            })
            ->values();

        $upcomingSchedules = Schedule::whereDate('date', '>=', Carbon::today())
            ->where('status', 'available')
            ->orderBy('date')
            ->orderBy('start_time')
            ->take(5)
            ->get()
            ->map(function ($schedule) {
                return [
                    'id' => $schedule->id,
                    'date' => $schedule->date ? Carbon::parse($schedule->date)->format('M d, Y') : '',
                    'time_slot' => $schedule->time_slot
                        ?? (
                            ($schedule->start_time && $schedule->end_time)
                                ? Carbon::parse($schedule->start_time)->format('h:i A') . ' - ' . Carbon::parse($schedule->end_time)->format('h:i A')
                                : ''
                        ),
                    'available_slots' => (int) ($schedule->available_slots ?? 0),
                    'total_capacity' => (int) ($schedule->total_capacity ?? 0),
                    'price_per_slot' => (float) ($schedule->price_per_slot ?? 0),
                ];
            })
            ->values();

        return response()->json([
            'admin' => [
                'name' => $user?->name ?? 'Admin',
                'email' => $user?->email ?? '',
            ],
            'overview' => [
                'totalUsers' => (int) $totalUsers,
                'totalBookings' => (int) $totalBookings,
                'totalRevenue' => (float) $totalRevenue,
                'pendingRefunds' => (int) $pendingRefunds,
                'todayBookings' => (int) $todayBookings,
                'todayRevenue' => (float) $todayRevenue,
            ],
            'monthlyRevenue' => $monthlyRevenue,
            'bookingStats' => [
                'pending' => (int) ($bookingStatsRaw['pending'] ?? 0),
                'confirmed' => (int) ($bookingStatsRaw['confirmed'] ?? 0),
                'completed' => (int) ($bookingStatsRaw['completed'] ?? 0),
                'cancelled' => (int) ($bookingStatsRaw['cancelled'] ?? 0),
                'no_show' => (int) ($bookingStatsRaw['no_show'] ?? 0),
            ],
            'recentBookings' => $recentBookings,
            'recentActivities' => $recentActivities,
            'upcomingSchedules' => $upcomingSchedules,
        ]);
    }
}