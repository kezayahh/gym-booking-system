<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\Schedule;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ScheduleController extends Controller
{
    public function index(Request $request)
    {
        $month = (int) $request->get('month', Carbon::now()->month);
        $year = (int) $request->get('year', Carbon::now()->year);
        $user = Auth::user();

        $schedules = Schedule::whereYear('date', $year)
            ->whereMonth('date', $month)
            ->whereDate('date', '>=', Carbon::today())
            ->orderBy('date')
            ->orderBy('start_time')
            ->get()
            ->map(fn ($schedule) => $this->transformSchedule($schedule, $user))
            ->values();

        $upcomingSchedules = Schedule::whereDate('date', '>=', Carbon::today())
            ->whereDate('date', '<=', Carbon::today()->copy()->addDays(7))
            ->orderBy('date')
            ->orderBy('start_time')
            ->get()
            ->map(fn ($schedule) => $this->transformSchedule($schedule, $user))
            ->filter(function ($schedule) {
                return $schedule['status'] === 'available'
                    && !$schedule['is_full']
                    && !$schedule['user_has_booking'];
            })
            ->values();

        $unreadNotifications = Notification::where('user_id', $user->id)
            ->where('is_read', false)
            ->count();

        return response()->json([
            'schedules' => $schedules,
            'upcomingSchedules' => $upcomingSchedules,
            'user' => [
                'name' => $user->name,
                'role' => 'Member',
            ],
            'unreadNotifications' => $unreadNotifications,
        ]);
    }

    public function getSchedulesByDate(Request $request)
    {
        $request->validate([
            'date' => ['required', 'date'],
        ]);

        $user = Auth::user();

        $schedules = Schedule::whereDate('date', $request->date)
            ->orderBy('start_time')
            ->get()
            ->map(fn ($schedule) => $this->transformSchedule($schedule, $user))
            ->values();

        return response()->json($schedules);
    }

    public function show($id)
    {
        $user = Auth::user();
        $schedule = Schedule::findOrFail($id);

        return response()->json(
            $this->transformSchedule($schedule, $user, true)
        );
    }

    private function transformSchedule(Schedule $schedule, $user, bool $includeDetails = false): array
    {
        $date = $schedule->date instanceof Carbon
            ? $schedule->date
            : Carbon::parse($schedule->date);

        $startTime = $schedule->start_time;
        $endTime = $schedule->end_time;

        $availableSlots = max(0, (int) $schedule->total_capacity - (int) $schedule->booked_slots);

        $userHasBooking = method_exists($schedule, 'bookings')
            ? $schedule->bookings()
                ->where('user_id', $user->id)
                ->whereIn('status', ['pending', 'confirmed', 'paid'])
                ->exists()
            : false;

        $durationHours = 1;

        if ($startTime && $endTime) {
            try {
                $start = Carbon::parse($startTime);
                $end = Carbon::parse($endTime);
                $minutes = $start->diffInMinutes($end, false);

                if ($minutes > 0) {
                    $durationHours = round($minutes / 60, 2);
                }
            } catch (\Throwable $e) {
                $durationHours = 1;
            }
        }

        $pricePerSlot = (float) ($schedule->price_per_slot ?? 0);
        $isAvailableStatus = $schedule->status === 'available';
        $isFull = $availableSlots <= 0 || !$isAvailableStatus;

        $data = [
            'id' => $schedule->id,
            'date' => $date->format('Y-m-d'),
            'raw_date' => $date->format('Y-m-d'),
            'formatted_date' => $date->format('l, F d, Y'),
            'start_time' => $startTime,
            'end_time' => $endTime,
            'time_slot' => $schedule->timeSlot ?? ($startTime . ' - ' . $endTime),
            'total_capacity' => (int) $schedule->total_capacity,
            'booked_slots' => (int) $schedule->booked_slots,
            'available_slots' => $availableSlots,
            'price' => $pricePerSlot,
            'price_per_slot' => $pricePerSlot,
            'total_price' => $pricePerSlot,
            'status' => $schedule->status,
            'notes' => $schedule->notes,
            'is_full' => $isFull,
            'user_has_booking' => $userHasBooking,
            'duration_hours' => $durationHours,
        ];

        if ($includeDetails) {
            $data['display_date'] = $date->format('l, F d, Y');
        }

        return $data;
    }
}