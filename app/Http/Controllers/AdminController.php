<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AdminController extends Controller
{
    // Dashboard
    public function dashboard()
    {
        $data = [
            'totalUsers' => 0,
            'totalBookings' => 1,
            'totalPayments' => 1500,
            'totalRefunds' => 0,
            'booking' => (object)[
                'id' => '123456-78901',
                'date' => 'October 27, 2025',
                'facility' => 'Main Gym',
                'address' => 'Brgy. Balibayon',
                'time' => '8:00am - 11:00am | 3 hours',
                'status' => 'Confirmed'
            ]
        ];
        return view('admin.dashboard', $data);
    }

    // User Management
    public function users()
    {
        return view('admin.user-management');
    }

    // Booking Management
    public function bookings()
    {
        return view('admin.booking-management');
    }

    // Payment Management
    public function payments()
    {
        return view('admin.payment-management');
    }

    // Refund Management
    public function refunds()
    {
        return view('admin.refund-management');
    }

    // Reports
    public function reports()
    {
        return view('admin.reports');
    }

    // Profile
    public function profile()
    {
        return view('admin.profile');
    }
}