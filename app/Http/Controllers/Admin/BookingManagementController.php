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
    public function stats()
    {
        $today = Carbon::today();

        $todayRevenue = Booking::whereDate('booking_date', $today)
            ->whereHas('payment', function ($q) {
                $q->where('status', 'completed');
            })
            ->sum('total_amount');

        return response()->json([
            'totalBookings' => Booking::count(),
            'pendingBookings' => Booking::where('status', 'pending')->count(),
            'confirmedBookings' => Booking::where('status', 'confirmed')->count(),
            'todayRevenue' => $todayRevenue,
        ]);
    }

    public function index(Request $request)
    {
        $query = Booking::with(['user', 'schedule', 'payment']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('booking_number', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('booking_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('booking_date', '<=', $request->date_to);
        }

        $bookings = $query
            ->latest()
            ->get()
            ->map(function ($booking) {
                $scheduleDate = $booking->schedule?->date
                    ? Carbon::parse($booking->schedule->date)
                    : null;

                return [
                    'id' => $booking->id,
                    'booking_number' => $booking->booking_number,
                    'number_of_slots' => $booking->number_of_slots,
                    'total_amount' => (float) $booking->total_amount,
                    'special_requests' => $booking->special_requests,
                    'status' => $booking->status,
                    'booking_date' => $booking->booking_date
                        ? Carbon::parse($booking->booking_date)->format('Y-m-d')
                        : null,
                    'created_at' => $booking->created_at?->toDateTimeString(),
                    'created_at_formatted' => $booking->created_at
                        ? $booking->created_at->format('M d, Y h:i A')
                        : '',
                    'user' => $booking->user ? [
                        'id' => $booking->user->id,
                        'name' => $booking->user->name,
                        'email' => $booking->user->email,
                    ] : null,
                    'schedule' => $booking->schedule ? [
                        'id' => $booking->schedule->id,
                        'date' => $scheduleDate ? $scheduleDate->format('Y-m-d') : null,
                        'date_formatted' => $scheduleDate ? $scheduleDate->format('M d, Y') : '',
                        'timeSlot' => date('h:i A', strtotime($booking->schedule->start_time)) . ' - ' . date('h:i A', strtotime($booking->schedule->end_time)),
                        'start_time' => $booking->schedule->start_time,
                        'end_time' => $booking->schedule->end_time,
                    ] : null,
                    'payment' => $booking->payment ? [
                        'id' => $booking->payment->id,
                        'status' => $booking->payment->status,
                        'amount' => (float) $booking->payment->amount,
                    ] : null,
                ];
            })
            ->values();

        return response()->json([
            'bookings' => $bookings,
        ]);
    }

    public function activeUsers(Request $request)
    {
        $search = $request->get('search', '');

        $users = User::where('role', 'user')
            ->where('status', 'active')
            ->where(function ($q) use ($search) {
                if ($search !== '') {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                }
            })
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        return response()->json([
            'users' => $users,
        ]);
    }

    public function availableSchedules()
    {
        $schedules = Schedule::where('status', 'available')
            ->whereDate('date', '>=', Carbon::today())
            ->orderBy('date')
            ->orderBy('start_time')
            ->get()
            ->map(function ($schedule) {
                $availableSlots = max(0, (int) $schedule->total_capacity - (int) $schedule->booked_slots);

                return [
                    'id' => $schedule->id,
                    'date' => $schedule->date ? Carbon::parse($schedule->date)->format('Y-m-d') : null,
                    'date_formatted' => $schedule->date ? Carbon::parse($schedule->date)->format('M d, Y') : '',
                    'timeSlot' => date('h:i A', strtotime($schedule->start_time)) . ' - ' . date('h:i A', strtotime($schedule->end_time)),
                    'availableSlots' => $availableSlots,
                    'price_per_slot' => (float) $schedule->price_per_slot,
                ];
            })
            ->values();

        return response()->json([
            'schedules' => $schedules,
        ]);
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
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            DB::beginTransaction();

            $schedule = Schedule::findOrFail($request->schedule_id);

            if (method_exists($schedule, 'canBook')) {
                if (!$schedule->canBook($request->number_of_slots)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Schedule cannot accommodate the requested number of slots.',
                    ], 422);
                }
            } else {
                $availableSlots = max(0, (int) $schedule->total_capacity - (int) $schedule->booked_slots);
                if ((int) $request->number_of_slots > $availableSlots) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Schedule cannot accommodate the requested number of slots.',
                    ], 422);
                }
            }

            $booking = Booking::create([
                'user_id' => $request->user_id,
                'schedule_id' => $request->schedule_id,
                'number_of_slots' => $request->number_of_slots,
                'total_amount' => $schedule->price_per_slot * $request->number_of_slots,
                'booking_date' => $schedule->date,
                'special_requests' => $request->special_requests,
                'status' => 'confirmed',
            ]);

            if (method_exists($schedule, 'incrementBookedSlots')) {
                $schedule->incrementBookedSlots($request->number_of_slots);
            } else {
                $schedule->booked_slots = (int) $schedule->booked_slots + (int) $request->number_of_slots;
                if ($schedule->booked_slots >= $schedule->total_capacity) {
                    $schedule->status = 'full';
                }
                $schedule->save();
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Booking created successfully!',
                'booking' => $booking->load(['user', 'schedule']),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to create booking: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function show($id)
    {
        $booking = Booking::with(['user', 'schedule', 'payment', 'refund'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'booking' => $booking,
        ]);
    }

    public function confirm($id)
    {
        $booking = Booking::findOrFail($id);

        if ($booking->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Only pending bookings can be confirmed.',
            ], 422);
        }

        try {
            DB::beginTransaction();

            if (method_exists($booking, 'confirm')) {
                $booking->confirm();
            } else {
                $booking->status = 'confirmed';
                $booking->save();
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Booking confirmed successfully!',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to confirm booking: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function complete($id)
    {
        $booking = Booking::findOrFail($id);

        if ($booking->status !== 'confirmed') {
            return response()->json([
                'success' => false,
                'message' => 'Only confirmed bookings can be completed.',
            ], 422);
        }

        try {
            DB::beginTransaction();

            if (method_exists($booking, 'complete')) {
                $booking->complete();
            } else {
                $booking->status = 'completed';
                $booking->save();
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Booking completed successfully!',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to complete booking: ' . $e->getMessage(),
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
                'errors' => $validator->errors(),
            ], 422);
        }

        if (!in_array($booking->status, ['pending', 'confirmed'])) {
            return response()->json([
                'success' => false,
                'message' => 'This booking cannot be cancelled.',
            ], 422);
        }

        try {
            DB::beginTransaction();

            if (method_exists($booking, 'cancel')) {
                $booking->cancel($request->cancellation_reason);
            } else {
                $booking->status = 'cancelled';
                $booking->cancellation_reason = $request->cancellation_reason;
                $booking->save();

                if ($booking->schedule) {
                    $booking->schedule->booked_slots = max(0, (int) $booking->schedule->booked_slots - (int) $booking->number_of_slots);
                    if ($booking->schedule->booked_slots < $booking->schedule->total_capacity) {
                        $booking->schedule->status = 'available';
                    }
                    $booking->schedule->save();
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Booking cancelled successfully!',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel booking: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function destroy($id)
    {
        $booking = Booking::findOrFail($id);

        try {
            DB::beginTransaction();

            if ($booking->status !== 'cancelled' && $booking->schedule) {
                if (method_exists($booking->schedule, 'decrementBookedSlots')) {
                    $booking->schedule->decrementBookedSlots($booking->number_of_slots);
                } else {
                    $booking->schedule->booked_slots = max(0, (int) $booking->schedule->booked_slots - (int) $booking->number_of_slots);
                    if ($booking->schedule->booked_slots < $booking->schedule->total_capacity) {
                        $booking->schedule->status = 'available';
                    }
                    $booking->schedule->save();
                }
            }

            $booking->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Booking deleted successfully!',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete booking: ' . $e->getMessage(),
            ], 500);
        }
    }
}