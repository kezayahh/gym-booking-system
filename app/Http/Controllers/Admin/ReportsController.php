<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Refund;
use App\Models\User;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReportsController extends Controller
{
    /**
     * Display reports and analytics
     */
    public function index(Request $request)
    {
        // Date range from request or default to last 30 days
        $startDate = $request->get('start', now()->subDays(30)->format('Y-m-d'));
        $endDate = $request->get('end', now()->format('Y-m-d'));
        
        // Overview Statistics
        $totalBookings = Booking::whereBetween('created_at', [$startDate, $endDate])->count();
        
        $totalRevenue = Payment::where('status', 'completed')
            ->whereBetween('paid_at', [$startDate, $endDate])
            ->sum('amount');
        
        $totalUsers = User::where('role', 'user')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();
        
        $totalRefunds = Refund::whereIn('status', ['completed'])
            ->whereBetween('processed_at', [$startDate, $endDate])
            ->sum('refund_amount');
        
        // Revenue Chart Data (Last 7 days)
        $revenueChartData = $this->getRevenueChartData($startDate, $endDate);
        
        // Booking Status Distribution
        $bookingStatusData = $this->getBookingStatusData($startDate, $endDate);
        
        // Payment Methods Distribution
        $paymentMethodsData = $this->getPaymentMethodsData($startDate, $endDate);
        
        // Monthly Comparison (Last 6 months)
        $monthlyComparisonData = $this->getMonthlyComparisonData();
        
        // Top Users by Bookings
        $topUsers = User::where('role', 'user')
            ->withCount(['bookings' => function($query) use ($startDate, $endDate) {
                $query->whereBetween('created_at', [$startDate, $endDate]);
            }])
            ->with(['payments' => function($query) use ($startDate, $endDate) {
                $query->where('status', 'completed')
                      ->whereBetween('paid_at', [$startDate, $endDate]);
            }])
            ->get()
            ->map(function($user) {
                $user->total_spent = $user->payments->sum('amount');
                return $user;
            })
            ->sortByDesc('bookings_count')
            ->take(10);
        
        // Recent Activity
        $recentActivities = ActivityLog::with('user')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->latest()
            ->take(20)
            ->get();
        
        return view('admin.reports', compact(
            'startDate',
            'endDate',
            'totalBookings',
            'totalRevenue',
            'totalUsers',
            'totalRefunds',
            'revenueChartData',
            'bookingStatusData',
            'paymentMethodsData',
            'monthlyComparisonData',
            'topUsers',
            'recentActivities'
        ));
    }
    
    /**
     * Get revenue chart data
     */
    private function getRevenueChartData($startDate, $endDate)
    {
        $payments = Payment::where('status', 'completed')
            ->whereBetween('paid_at', [$startDate, $endDate])
            ->select(DB::raw('DATE(paid_at) as date'), DB::raw('SUM(amount) as total'))
            ->groupBy('date')
            ->orderBy('date')
            ->get();
        
        return [
            'labels' => $payments->pluck('date')->map(function($date) {
                return Carbon::parse($date)->format('M d');
            })->toArray(),
            'values' => $payments->pluck('total')->toArray()
        ];
    }
    
    /**
     * Get booking status distribution
     */
    private function getBookingStatusData($startDate, $endDate)
    {
        $bookings = Booking::whereBetween('created_at', [$startDate, $endDate])
            ->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->get();
        
        return [
            'labels' => $bookings->pluck('status')->map(function($status) {
                return ucfirst($status);
            })->toArray(),
            'values' => $bookings->pluck('count')->toArray()
        ];
    }
    
    /**
     * Get payment methods distribution
     */
    private function getPaymentMethodsData($startDate, $endDate)
    {
        $payments = Payment::where('status', 'completed')
            ->whereBetween('paid_at', [$startDate, $endDate])
            ->select('payment_method', DB::raw('COUNT(*) as count'))
            ->groupBy('payment_method')
            ->get();
        
        return [
            'labels' => $payments->pluck('payment_method')->map(function($method) {
                return ucfirst($method);
            })->toArray(),
            'values' => $payments->pluck('count')->toArray()
        ];
    }
    
    /**
     * Get monthly revenue comparison
     */
    private function getMonthlyComparisonData()
    {
        $months = collect();
        
        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $monthName = $date->format('M Y');
            
            $revenue = Payment::where('status', 'completed')
                ->whereYear('paid_at', $date->year)
                ->whereMonth('paid_at', $date->month)
                ->sum('amount');
            
            $months->push([
                'month' => $monthName,
                'revenue' => $revenue
            ]);
        }
        
        return [
            'labels' => $months->pluck('month')->toArray(),
            'values' => $months->pluck('revenue')->toArray()
        ];
    }
    
    /**
     * Export report as CSV
     */
    public function exportCSV(Request $request)
    {
        $startDate = $request->get('start', now()->subDays(30)->format('Y-m-d'));
        $endDate = $request->get('end', now()->format('Y-m-d'));
        
        $bookings = Booking::with(['user', 'schedule', 'payment'])
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get();
        
        $filename = 'gym-report-' . now()->format('Y-m-d') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];
        
        $callback = function() use ($bookings) {
            $file = fopen('php://output', 'w');
            
            // Headers
            fputcsv($file, [
                'Booking Number',
                'User',
                'Date',
                'Time Slot',
                'Slots',
                'Amount',
                'Status',
                'Payment Status'
            ]);
            
            // Data
            foreach ($bookings as $booking) {
                fputcsv($file, [
                    $booking->booking_number,
                    $booking->user->name,
                    $booking->schedule->date->format('Y-m-d'),
                    $booking->schedule->time_slot,
                    $booking->number_of_slots,
                    $booking->total_amount,
                    $booking->status,
                    $booking->payment ? $booking->payment->status : 'unpaid'
                ]);
            }
            
            fclose($file);
        };
        
        return response()->stream($callback, 200, $headers);
    }
}