<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Schedule;
use App\Models\Payment;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class BookingController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $bookings = Booking::with('schedule')
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        $availableSchedules = Schedule::where('status', 'available')
            ->where('date', '>=', Carbon::today())
            ->orderBy('date')
            ->orderBy('start_time')
            ->get()
            ->map(function ($schedule) {
                return [
                    'id' => $schedule->id,
                    'date' => optional($schedule->date)->format('M d, Y'),
                    'time_slot' => $schedule->timeSlot ?? $schedule->time_slot ?? ($schedule->start_time . ' - ' . $schedule->end_time),
                    'price_per_slot' => (float) $schedule->price_per_slot,
                    'available_slots' => max(0, (int) $schedule->total_capacity - (int) $schedule->booked_slots),
                ];
            })
            ->filter(function ($schedule) {
                return $schedule['available_slots'] > 0;
            })
            ->values();

        $bookingItems = collect($bookings->items())->map(function ($booking) {
            return [
                'id' => $booking->id,
                'booking_number' => $booking->booking_number,
                'created_at' => $booking->created_at?->format('M d, Y'),
                'number_of_slots' => $booking->number_of_slots,
                'total_amount' => (float) $booking->total_amount,
                'status' => $booking->status,
                'status_label' => ucfirst($booking->status),
                'is_paid' => (bool) $booking->isPaid,
                'can_cancel' => (bool) $booking->canCancel,
                'schedule' => [
                    'date' => optional($booking->schedule?->date)->format('M d, Y'),
                    'time_slot' => $booking->schedule?->timeSlot ?? $booking->schedule?->time_slot ?? null,
                ],
            ];
        })->values();

        $allBookings = Booking::where('user_id', $user->id)->get();

        $stats = [
            'total' => $bookings->total(),
            'pending' => $allBookings->where('status', 'pending')->count(),
            'confirmed' => $allBookings->where('status', 'confirmed')->count(),
            'cancelled' => $allBookings->where('status', 'cancelled')->count(),
        ];

        $unreadNotifications = Notification::where('user_id', $user->id)
            ->where('is_read', false)
            ->count();

        return response()->json([
            'user' => [
                'name' => $user->name,
                'role' => 'Member',
            ],
            'unreadNotifications' => $unreadNotifications,
            'stats' => $stats,
            'bookings' => [
                'data' => $bookingItems,
                'current_page' => $bookings->currentPage(),
                'last_page' => $bookings->lastPage(),
                'per_page' => $bookings->perPage(),
                'total' => $bookings->total(),
                'from' => $bookings->firstItem(),
                'to' => $bookings->lastItem(),
                'prev_page_url' => $bookings->previousPageUrl(),
                'next_page_url' => $bookings->nextPageUrl(),
            ],
            'availableSchedules' => $availableSchedules,
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'schedule_id'      => 'required|exists:schedules,id',
            'number_of_slots'  => 'required|integer|min:1',
            'payment_method'   => 'required|in:cash,gcash,maya,bank_transfer',
            'special_requests' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'errors'  => $validator->errors(),
            ], 422);
        }

        $schedule = Schedule::findOrFail($request->schedule_id);
        $numberOfSlots = (int) $request->number_of_slots;
        $availableSlots = max(0, (int) $schedule->total_capacity - (int) $schedule->booked_slots);

        $alreadyBooked = Booking::where('user_id', Auth::id())
            ->where('schedule_id', $schedule->id)
            ->whereIn('status', ['pending', 'paid', 'confirmed'])
            ->exists();

        if ($alreadyBooked) {
            return response()->json([
                'success' => false,
                'message' => 'You already have a booking for this schedule.',
            ], 400);
        }

        if (method_exists($schedule, 'canBook')) {
            if (! $schedule->canBook($numberOfSlots)) {
                return response()->json([
                    'success' => false,
                    'message' => 'This schedule is no longer available or does not have enough capacity.',
                ], 400);
            }
        } else {
            if ($schedule->status !== 'available') {
                return response()->json([
                    'success' => false,
                    'message' => 'This schedule is not available for booking.',
                ], 400);
            }
        }

        if ($availableSlots < $numberOfSlots) {
            return response()->json([
                'success' => false,
                'message' => 'Not enough slots available. Only ' . $availableSlots . ' slot(s) remaining.',
            ], 400);
        }

        DB::beginTransaction();

        try {
            $durationHours = 1;

            if ($schedule->start_time && $schedule->end_time) {
                try {
                    $start = Carbon::parse($schedule->start_time);
                    $end = Carbon::parse($schedule->end_time);
                    $minutes = $start->diffInMinutes($end, false);

                    if ($minutes > 0) {
                        $durationHours = round($minutes / 60, 2);
                    }
                } catch (\Throwable $e) {
                    $durationHours = 1;
                }
            }

            $pricePerSlot = (float) $schedule->price_per_slot;
            $totalAmount = $pricePerSlot * $durationHours * $numberOfSlots;

            $booking = Booking::create([
                'user_id'          => Auth::id(),
                'schedule_id'      => $schedule->id,
                'number_of_slots'  => $numberOfSlots,
                'total_amount'     => $totalAmount,
                'booking_date'     => $schedule->date,
                'status'           => 'pending',
                'special_requests' => $request->special_requests,
            ]);

            if (method_exists($schedule, 'incrementBookedSlots')) {
                $schedule->incrementBookedSlots($numberOfSlots);
            } else {
                $schedule->booked_slots = (int) $schedule->booked_slots + $numberOfSlots;

                if ((int) $schedule->booked_slots >= (int) $schedule->total_capacity) {
                    $schedule->status = 'full';
                } else {
                    $schedule->status = 'available';
                }

                $schedule->save();
            }

            $payment = Payment::create([
                'booking_id'     => $booking->id,
                'user_id'        => Auth::id(),
                'amount'         => $booking->total_amount,
                'payment_method' => $request->payment_method,
                'status'         => 'pending',
            ]);

            Notification::create([
                'user_id' => Auth::id(),
                'type' => 'booking_confirmation',
                'title' => 'Booking Created',
                'message' => "Your booking {$booking->booking_number} has been created successfully.",
                'data' => [
                    'booking_id' => $booking->id,
                    'payment_id' => $payment->id,
                ],
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Booking created successfully!',
                'booking' => [
                    'id'             => $booking->id,
                    'booking_number' => $booking->booking_number,
                    'total_amount'   => $booking->total_amount,
                    'payment_id'     => $payment->id,
                    'payment_method' => $payment->payment_method,
                    'status'         => $booking->status,
                    'schedule' => [
                        'id' => $schedule->id,
                        'date' => optional($schedule->date)->format('Y-m-d'),
                        'formatted_date' => optional($schedule->date)->format('l, F d, Y'),
                        'display_date' => optional($schedule->date)->format('l, F d, Y'),
                        'time_slot' => $schedule->timeSlot ?? $schedule->time_slot ?? ($schedule->start_time . ' - ' . $schedule->end_time),
                        'duration_hours' => $durationHours,
                        'price_per_slot' => $pricePerSlot,
                        'available_slots' => max(0, (int) $schedule->total_capacity - (int) $schedule->booked_slots),
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Booking error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function cancel(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'reason' => 'required|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        $booking = Booking::with(['schedule', 'payment'])
            ->where('id', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        if (! $booking->canCancel) {
            return response()->json([
                'success' => false,
                'message' => 'This booking cannot be cancelled. Must be cancelled at least 24 hours before the scheduled time.',
            ], 400);
        }

        DB::beginTransaction();

        try {
            if (method_exists($booking, 'cancel')) {
                $booking->cancel($request->reason);
            } else {
                $booking->status = 'cancelled';
                if (property_exists($booking, 'cancellation_reason') || isset($booking->cancellation_reason)) {
                    $booking->cancellation_reason = $request->reason;
                }
                $booking->save();

                if ($booking->schedule) {
                    $schedule = $booking->schedule;
                    $schedule->booked_slots = max(0, (int) $schedule->booked_slots - (int) $booking->number_of_slots);

                    if ((int) $schedule->booked_slots < (int) $schedule->total_capacity) {
                        $schedule->status = 'available';
                    }

                    $schedule->save();
                }

                if ($booking->payment && $booking->payment->status === 'pending') {
                    $booking->payment->status = 'failed';
                    $booking->payment->save();
                }
            }

            Notification::create([
                'user_id' => Auth::id(),
                'type' => 'booking_cancelled',
                'title' => 'Booking Cancelled',
                'message' => "Your booking {$booking->booking_number} has been cancelled.",
                'data' => [
                    'booking_id' => $booking->id,
                ],
            ]);

            if (function_exists('activity')) {
                activity()
                    ->causedBy(Auth::user())
                    ->performedOn($booking)
                    ->log('Cancelled booking: ' . $booking->booking_number);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Booking cancelled successfully.',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Cancel booking error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function details($bookingId)
    {
        $booking = Booking::with(['schedule', 'payment'])
            ->where('id', $bookingId)
            ->where('user_id', auth()->id())
            ->first();

        if (! $booking) {
            return response()->json([
                'success' => false,
                'message' => 'Booking not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'booking_number' => $booking->booking_number,
                'date' => optional($booking->schedule?->date)->format('M d, Y'),
                'time' => $booking->schedule?->timeSlot ?? $booking->schedule?->time_slot ?? null,
                'slots' => $booking->number_of_slots,
                'total' => $booking->total_amount,
                'status' => ucfirst($booking->status),
                'payment' => $booking->isPaid ? 'Paid' : 'Unpaid',
                'requests' => $booking->special_requests,
            ],
        ]);
    }
}