<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Refund;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportsController extends Controller
{
    public function index(Request $request)
    {
        $startDate = $request->get('start', now()->subDays(30)->format('Y-m-d'));
        $endDate = $request->get('end', now()->format('Y-m-d'));

        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->endOfDay();

        $totalBookings = Booking::whereBetween('created_at', [$start, $end])->count();

        $totalRevenue = Payment::where('status', 'completed')
            ->whereBetween('paid_at', [$start, $end])
            ->sum('amount');

        $totalUsers = User::where('role', 'user')
            ->whereBetween('created_at', [$start, $end])
            ->count();

        $totalRefunds = Refund::where('status', 'completed')
            ->whereBetween('processed_at', [$start, $end])
            ->sum('refund_amount');

        $revenueChartData = $this->getRevenueChartData($start, $end);
        $bookingStatusData = $this->getBookingStatusData($start, $end);
        $paymentMethodsData = $this->getPaymentMethodsData($start, $end);
        $monthlyComparisonData = $this->getMonthlyComparisonData();

        $topUsers = User::where('role', 'user')
            ->withCount([
                'bookings' => function ($query) use ($start, $end) {
                    $query->whereBetween('created_at', [$start, $end]);
                }
            ])
            ->with([
                'payments' => function ($query) use ($start, $end) {
                    $query->where('status', 'completed')
                        ->whereBetween('paid_at', [$start, $end]);
                }
            ])
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'status' => $user->status,
                    'bookings_count' => $user->bookings_count ?? 0,
                    'total_spent' => (float) $user->payments->sum('amount'),
                    'profile_picture_url' => $user->profile_picture_url,
                ];
            })
            ->sortByDesc('bookings_count')
            ->take(10)
            ->values();

        $recentActivities = ActivityLog::with('user')
            ->whereBetween('created_at', [$start, $end])
            ->latest()
            ->take(20)
            ->get()
            ->map(function ($activity) {
                return [
                    'id' => $activity->id,
                    'action' => $activity->action,
                    'description' => $activity->description,
                    'created_at' => $activity->created_at?->toISOString(),
                    'user' => $activity->user ? [
                        'id' => $activity->user->id,
                        'name' => $activity->user->name,
                        'email' => $activity->user->email,
                    ] : null,
                ];
            })
            ->values();

        return response()->json([
            'startDate' => $startDate,
            'endDate' => $endDate,
            'stats' => [
                'totalBookings' => $totalBookings,
                'totalRevenue' => (float) $totalRevenue,
                'totalUsers' => $totalUsers,
                'totalRefunds' => (float) $totalRefunds,
            ],
            'revenueChartData' => $revenueChartData,
            'bookingStatusData' => $bookingStatusData,
            'paymentMethodsData' => $paymentMethodsData,
            'monthlyComparisonData' => $monthlyComparisonData,
            'topUsers' => $topUsers,
            'recentActivities' => $recentActivities,
        ]);
    }

    private function getRevenueChartData($start, $end)
    {
        $payments = Payment::where('status', 'completed')
            ->whereBetween('paid_at', [$start, $end])
            ->select(DB::raw('DATE(paid_at) as date'), DB::raw('SUM(amount) as total'))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return [
            'labels' => $payments->pluck('date')->map(function ($date) {
                return Carbon::parse($date)->format('M d');
            })->toArray(),
            'values' => $payments->pluck('total')->map(fn ($v) => (float) $v)->toArray(),
        ];
    }

    private function getBookingStatusData($start, $end)
    {
        $bookings = Booking::whereBetween('created_at', [$start, $end])
            ->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->get();

        return [
            'labels' => $bookings->pluck('status')->map(function ($status) {
                return ucfirst(str_replace('_', ' ', $status));
            })->toArray(),
            'values' => $bookings->pluck('count')->map(fn ($v) => (int) $v)->toArray(),
        ];
    }

    private function getPaymentMethodsData($start, $end)
    {
        $payments = Payment::where('status', 'completed')
            ->whereBetween('paid_at', [$start, $end])
            ->select('payment_method', DB::raw('COUNT(*) as count'))
            ->groupBy('payment_method')
            ->get();

        return [
            'labels' => $payments->pluck('payment_method')->map(function ($method) {
                return ucwords(str_replace('_', ' ', $method));
            })->toArray(),
            'values' => $payments->pluck('count')->map(fn ($v) => (int) $v)->toArray(),
        ];
    }

    private function getMonthlyComparisonData()
    {
        $months = collect();

        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);

            $revenue = Payment::where('status', 'completed')
                ->whereYear('paid_at', $date->year)
                ->whereMonth('paid_at', $date->month)
                ->sum('amount');

            $months->push([
                'month' => $date->format('M Y'),
                'revenue' => (float) $revenue,
            ]);
        }

        return [
            'labels' => $months->pluck('month')->toArray(),
            'values' => $months->pluck('revenue')->toArray(),
        ];
    }

    public function exportCSV(Request $request)
    {
        $startDate = $request->get('start', now()->subDays(30)->format('Y-m-d'));
        $endDate = $request->get('end', now()->format('Y-m-d'));

        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->endOfDay();

        $bookings = Booking::with(['user', 'schedule', 'payment'])
            ->whereBetween('created_at', [$start, $end])
            ->get();

        $filename = 'gym-report-' . now()->format('Y-m-d_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function () use ($bookings) {
            $file = fopen('php://output', 'w');

            fputcsv($file, [
                'Booking Number',
                'User',
                'Date',
                'Time Slot',
                'Slots',
                'Amount',
                'Status',
                'Payment Status',
            ]);

            foreach ($bookings as $booking) {
                fputcsv($file, [
                    $booking->booking_number,
                    $booking->user?->name ?? 'N/A',
                    $booking->schedule?->date ? Carbon::parse($booking->schedule->date)->format('Y-m-d') : 'N/A',
                    $booking->schedule?->time_slot ?? 'N/A',
                    $booking->number_of_slots,
                    $booking->total_amount,
                    $booking->status,
                    $booking->payment?->status ?? 'unpaid',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}