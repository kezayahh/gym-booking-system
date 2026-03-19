<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $totalBookings = Booking::where('user_id', $user->id)->count();

        $confirmedBookings = Booking::where('user_id', $user->id)
            ->where('status', 'confirmed')
            ->count();

        $totalSpent = Payment::where('user_id', $user->id)
            ->where('status', 'completed')
            ->sum('amount');

        $pendingPayments = Payment::where('user_id', $user->id)
            ->where('status', 'pending')
            ->sum('amount');

        $upcomingBookings = Booking::with('schedule')
            ->where('user_id', $user->id)
            ->whereIn('status', ['pending', 'confirmed'])
            ->whereHas('schedule', function ($query) {
                $query->where('date', '>=', Carbon::today());
            })
            ->latest()
            ->take(10)
            ->get()
            ->map(function ($booking) {
                return [
                    'id' => $booking->id,
                    'booking_number' => $booking->booking_number,
                    'status' => $booking->status,
                    'number_of_slots' => $booking->number_of_slots,
                    'total_amount' => $booking->total_amount,
                    'is_paid' => $booking->isPaid,
                    'schedule' => [
                        'formatted_date' => optional($booking->schedule->date)->format('l, F d, Y'),
                        'time_slot' => $booking->schedule->timeSlot ?? '',
                    ],
                ];
            })
            ->values();

        $recentPayments = Payment::where('user_id', $user->id)
            ->latest()
            ->take(5)
            ->get()
            ->map(function ($payment) {
                return [
                    'id' => $payment->id,
                    'payment_number' => $payment->payment_number,
                    'amount' => $payment->amount,
                    'status' => $payment->status,
                    'status_label' => ucfirst($payment->status),
                    'created_at' => $payment->created_at?->format('M d, Y'),
                ];
            })
            ->values();

        $notifications = Notification::where('user_id', $user->id)
            ->latest()
            ->take(10)
            ->get()
            ->map(function ($notification) {
                return [
                    'id' => $notification->id,
                    'title' => $notification->title,
                    'message' => $notification->message,
                    'short_message' => Str::limit($notification->message, 60),
                    'type' => $notification->type,
                    'is_read' => $notification->is_read,
                    'created_at' => $notification->created_at?->diffForHumans(),
                ];
            })
            ->values();

        return response()->json([
            'user' => [
                'name' => $user->name,
                'email' => $user->email,
                'memberSince' => $user->created_at?->format('M Y'),
                'initials' => strtoupper(Str::substr($user->name, 0, 2)),
            ],
            'stats' => [
                'totalBookings' => $totalBookings,
                'confirmedBookings' => $confirmedBookings,
                'totalSpent' => $totalSpent,
                'pendingPayments' => $pendingPayments,
            ],
            'upcomingBookings' => $upcomingBookings,
            'recentPayments' => $recentPayments,
            'notifications' => $notifications,
            'unreadNotifications' => Notification::where('user_id', $user->id)
                ->where('is_read', false)
                ->count(),
        ]);
    }
}