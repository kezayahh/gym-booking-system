<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class BookingManagementController extends Controller
{
    public function index(Request $request)
    {
        $query = Booking::with(['user', 'schedule', 'payment']);

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('booking_number', 'like', "%{$search}%")
                  ->orWhereHas('user', function($userQuery) use ($search) {
                      $userQuery->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('booking_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('booking_date', '<=', $request->date_to);
        }

        // Sort
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $bookings = $query->latest()->paginate(10);
        
        // Statistics
        $totalBookings = Booking::count();
        $pendingBookings = Booking::where('status', 'pending')->count();
        $confirmedBookings = Booking::where('status', 'confirmed')->count();
        $completedBookings = Booking::where('status', 'completed')->count();
        $cancelledBookings = Booking::where('status', 'cancelled')->count();
        
        $todayBookings = Booking::whereDate('booking_date', Carbon::today())->count();
        $todayRevenue = Booking::whereDate('booking_date', Carbon::today())
            ->whereHas('payment', function($q) {
                $q->where('status', 'completed');
            })->sum('total_amount');

        // Get available schedules for booking creation
        $availableSchedules = Schedule::where('status', 'available')
            ->where('date', '>=', Carbon::today())
            ->orderBy('date')
            ->orderBy('start_time')
            ->get();

        return view('admin.booking-management', compact(
            'bookings', 'totalBookings', 'pendingBookings', 'confirmedBookings',
            'completedBookings', 'cancelledBookings', 'todayBookings', 
            'todayRevenue', 'availableSchedules'
        ));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'schedule_id' => 'required|exists:schedules,id',
            'number_of_slots' => 'required|integer|min:1',
            'special_requests' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $schedule = Schedule::findOrFail($request->schedule_id);
            
            // Check if schedule can accommodate booking
            if (!$schedule->canBook($request->number_of_slots)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Schedule cannot accommodate the requested number of slots.'
                ], 422);
            }

            // Create booking
            $booking = Booking::create([
                'user_id' => $request->user_id,
                'schedule_id' => $request->schedule_id,
                'number_of_slots' => $request->number_of_slots,
                'total_amount' => $schedule->price_per_slot * $request->number_of_slots,
                'booking_date' => $schedule->date,
                'special_requests' => $request->special_requests,
                'status' => 'confirmed', // Admin bookings are auto-confirmed
            ]);

            // Update schedule slots
            $schedule->incrementBookedSlots($request->number_of_slots);

            // Log activity
            activity()
                ->causedBy(auth()->user())
                ->performedOn($booking)
                ->withProperties([
                    'booking_number' => $booking->booking_number,
                    'user_id' => $booking->user_id,
                    'schedule_id' => $booking->schedule_id
                ])
                ->log('Admin created booking: ' . $booking->booking_number);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Booking created successfully!',
                'booking' => $booking->load(['user', 'schedule'])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create booking: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        $booking = Booking::with(['user', 'schedule', 'payment', 'refund'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'booking' => $booking
        ]);
    }

    public function update(Request $request, $id)
    {
        $booking = Booking::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:pending,confirmed,completed,cancelled',
            'special_requests' => 'nullable|string|max:500',
            'admin_notes' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $oldStatus = $booking->status;
            $oldData = $booking->toArray();

            $booking->update([
                'status' => $request->status,
                'special_requests' => $request->special_requests,
            ]);

            // Log activity
            activity()
                ->causedBy(auth()->user())
                ->performedOn($booking)
                ->withProperties([
                    'old_status' => $oldStatus,
                    'new_status' => $request->status,
                    'booking_number' => $booking->booking_number
                ])
                ->log('Admin updated booking: ' . $booking->booking_number);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Booking updated successfully!',
                'booking' => $booking->load(['user', 'schedule'])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update booking: ' . $e->getMessage()
            ], 500);
        }
    }

    public function confirm($id)
    {
        $booking = Booking::findOrFail($id);

        if ($booking->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Only pending bookings can be confirmed.'
            ], 422);
        }

        try {
            DB::beginTransaction();

            $booking->confirm();

            // Log activity
            activity()
                ->causedBy(auth()->user())
                ->performedOn($booking)
                ->withProperties(['booking_number' => $booking->booking_number])
                ->log('Admin confirmed booking: ' . $booking->booking_number);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Booking confirmed successfully!'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to confirm booking: ' . $e->getMessage()
            ], 500);
        }
    }

    public function complete($id)
    {
        $booking = Booking::findOrFail($id);

        if ($booking->status !== 'confirmed') {
            return response()->json([
                'success' => false,
                'message' => 'Only confirmed bookings can be completed.'
            ], 422);
        }

        try {
            DB::beginTransaction();

            $booking->complete();

            // Log activity
            activity()
                ->causedBy(auth()->user())
                ->performedOn($booking)
                ->withProperties(['booking_number' => $booking->booking_number])
                ->log('Admin completed booking: ' . $booking->booking_number);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Booking completed successfully!'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to complete booking: ' . $e->getMessage()
            ], 500);
        }
    }

    public function cancel(Request $request, $id)
    {
        $booking = Booking::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'cancellation_reason' => 'required|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        if (!in_array($booking->status, ['pending', 'confirmed'])) {
            return response()->json([
                'success' => false,
                'message' => 'This booking cannot be cancelled.'
            ], 422);
        }

        try {
            DB::beginTransaction();

            $booking->cancel($request->cancellation_reason);

            // Log activity
            activity()
                ->causedBy(auth()->user())
                ->performedOn($booking)
                ->withProperties([
                    'booking_number' => $booking->booking_number,
                    'reason' => $request->cancellation_reason
                ])
                ->log('Admin cancelled booking: ' . $booking->booking_number);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Booking cancelled successfully!'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel booking: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        $booking = Booking::findOrFail($id);

        try {
            DB::beginTransaction();

            $bookingNumber = $booking->booking_number;

            // If booking is not cancelled, free up the slots
            if ($booking->status !== 'cancelled') {
                $booking->schedule->decrementBookedSlots($booking->number_of_slots);
            }

            // Log activity before deletion
            activity()
                ->causedBy(auth()->user())
                ->withProperties([
                    'booking_number' => $bookingNumber,
                    'user_id' => $booking->user_id
                ])
                ->log('Admin deleted booking: ' . $bookingNumber);

            $booking->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Booking deleted successfully!'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete booking: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getUsers(Request $request)
    {
        $search = $request->get('search', '');
        
        $users = User::where('role', 'user')
            ->where('status', 'active')
            ->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            })
            ->limit(10)
            ->get(['id', 'name', 'email']);

        return response()->json([
            'success' => true,
            'users' => $users
        ]);
    }
}