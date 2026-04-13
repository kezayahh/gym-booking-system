<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Schedule;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ScheduleManagementController extends Controller
{
    public function index(Request $request)
    {
        $query = Schedule::query();

        if ($request->filled('date')) {
            $query->whereDate('date', $request->date);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('start_date')) {
            $query->whereDate('date', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('date', '<=', $request->end_date);
        }

        $schedules = $query
            ->withCount('bookings')
            ->orderBy('date', 'desc')
            ->orderBy('start_time', 'asc')
            ->get()
            ->map(function ($schedule) {
                $start = strtotime($schedule->start_time);
                $end = strtotime($schedule->end_time);
                $durationHours = $end > $start ? ($end - $start) / 3600 : 0;

                return [
                    'id' => $schedule->id,
                    'date' => $schedule->date ? Carbon::parse($schedule->date)->format('Y-m-d') : null,
                    'start_time' => $schedule->start_time ? substr($schedule->start_time, 0, 5) : '',
                    'end_time' => $schedule->end_time ? substr($schedule->end_time, 0, 5) : '',
                    'time_slot' => date('h:i A', strtotime($schedule->start_time)) . ' - ' . date('h:i A', strtotime($schedule->end_time)),
                    'total_capacity' => (int) $schedule->total_capacity,
                    'booked_slots' => (int) $schedule->booked_slots,
                    'available_slots' => max(0, (int) $schedule->total_capacity - (int) $schedule->booked_slots),
                    'price_per_slot' => (float) $schedule->price_per_slot,
                    'status' => $schedule->status,
                    'notes' => $schedule->notes,
                    'duration_hours' => $durationHours,
                    'total_price' => $durationHours * (float) $schedule->price_per_slot,
                ];
            })
            ->values();

        $stats = [
            'totalSchedules' => Schedule::count(),
            'availableSchedules' => Schedule::where('status', 'available')->count(),
            'fullSchedules' => Schedule::where('status', 'full')->count(),
            'totalCapacity' => Schedule::sum('total_capacity'),
        ];

        return response()->json([
            'schedules' => $schedules,
            'stats' => $stats,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'date' => 'required|date|after_or_equal:today',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'total_capacity' => 'required|integer|min:1',
            'price_per_slot' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:500',
        ]);

        $overlapping = Schedule::whereDate('date', $validated['date'])
            ->where(function ($query) use ($validated) {
                $query->whereBetween('start_time', [$validated['start_time'], $validated['end_time']])
                    ->orWhereBetween('end_time', [$validated['start_time'], $validated['end_time']])
                    ->orWhere(function ($q) use ($validated) {
                        $q->where('start_time', '<=', $validated['start_time'])
                            ->where('end_time', '>=', $validated['end_time']);
                    });
            })
            ->exists();

        if ($overlapping) {
            return response()->json([
                'success' => false,
                'message' => 'This time slot overlaps with an existing schedule.',
            ], 422);
        }

        $schedule = Schedule::create([
            'date' => $validated['date'],
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
            'total_capacity' => $validated['total_capacity'],
            'booked_slots' => 0,
            'price_per_slot' => $validated['price_per_slot'],
            'status' => 'available',
            'notes' => $validated['notes'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Schedule created successfully.',
            'schedule' => $schedule,
        ]);
    }

    public function show($id)
    {
        $schedule = Schedule::findOrFail($id);

        return response()->json([
            'success' => true,
            'schedule' => [
                'id' => $schedule->id,
                'date' => $schedule->date ? Carbon::parse($schedule->date)->format('Y-m-d') : null,
                'start_time' => $schedule->start_time ? substr($schedule->start_time, 0, 5) : '',
                'end_time' => $schedule->end_time ? substr($schedule->end_time, 0, 5) : '',
                'time_slot' => date('h:i A', strtotime($schedule->start_time)) . ' - ' . date('h:i A', strtotime($schedule->end_time)),
                'total_capacity' => (int) $schedule->total_capacity,
                'booked_slots' => (int) $schedule->booked_slots,
                'available_slots' => max(0, (int) $schedule->total_capacity - (int) $schedule->booked_slots),
                'price_per_slot' => (float) $schedule->price_per_slot,
                'status' => $schedule->status,
                'notes' => $schedule->notes,
            ],
        ]);
    }

    public function update(Request $request, $id)
    {
        $schedule = Schedule::findOrFail($id);

        if ((int) $schedule->booked_slots > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot update schedule with existing bookings.',
            ], 422);
        }

        $validated = $request->validate([
            'date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'total_capacity' => 'required|integer|min:1',
            'price_per_slot' => 'required|numeric|min:0',
            'status' => 'required|in:available,full,closed',
            'notes' => 'nullable|string|max:500',
        ]);

        $overlapping = Schedule::whereDate('date', $validated['date'])
            ->where('id', '!=', $schedule->id)
            ->where(function ($query) use ($validated) {
                $query->whereBetween('start_time', [$validated['start_time'], $validated['end_time']])
                    ->orWhereBetween('end_time', [$validated['start_time'], $validated['end_time']])
                    ->orWhere(function ($q) use ($validated) {
                        $q->where('start_time', '<=', $validated['start_time'])
                            ->where('end_time', '>=', $validated['end_time']);
                    });
            })
            ->exists();

        if ($overlapping) {
            return response()->json([
                'success' => false,
                'message' => 'This time slot overlaps with an existing schedule.',
            ], 422);
        }

        $schedule->update([
            'date' => $validated['date'],
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
            'total_capacity' => $validated['total_capacity'],
            'price_per_slot' => $validated['price_per_slot'],
            'status' => $validated['status'],
            'notes' => $validated['notes'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Schedule updated successfully.',
            'schedule' => $schedule,
        ]);
    }

    public function destroy($id)
    {
        $schedule = Schedule::findOrFail($id);

        if ((int) $schedule->booked_slots > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete schedule with existing bookings.',
            ], 422);
        }

        $schedule->delete();

        return response()->json([
            'success' => true,
            'message' => 'Schedule deleted successfully.',
        ]);
    }

    public function bulkCreate(Request $request)
    {
        $validated = $request->validate([
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after_or_equal:start_date',
            'time_slots' => 'required|array|min:1',
            'time_slots.*.start_time' => 'required|date_format:H:i',
            'time_slots.*.end_time' => 'required|date_format:H:i',
            'time_slots.*.capacity' => 'required|integer|min:1',
            'time_slots.*.price' => 'required|numeric|min:0',
            'days_of_week' => 'required|array|min:1',
            'days_of_week.*' => 'integer|min:0|max:6',
        ]);

        $startDate = Carbon::parse($validated['start_date']);
        $endDate = Carbon::parse($validated['end_date']);
        $daysOfWeek = $validated['days_of_week'];
        $createdCount = 0;

        DB::beginTransaction();

        try {
            for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
                if (in_array($date->dayOfWeek, $daysOfWeek)) {
                    foreach ($validated['time_slots'] as $slot) {
                        if ($slot['end_time'] <= $slot['start_time']) {
                            continue;
                        }

                        $overlapping = Schedule::whereDate('date', $date->format('Y-m-d'))
                            ->where(function ($query) use ($slot) {
                                $query->whereBetween('start_time', [$slot['start_time'], $slot['end_time']])
                                    ->orWhereBetween('end_time', [$slot['start_time'], $slot['end_time']])
                                    ->orWhere(function ($q) use ($slot) {
                                        $q->where('start_time', '<=', $slot['start_time'])
                                            ->where('end_time', '>=', $slot['end_time']);
                                    });
                            })
                            ->exists();

                        if (!$overlapping) {
                            Schedule::create([
                                'date' => $date->format('Y-m-d'),
                                'start_time' => $slot['start_time'],
                                'end_time' => $slot['end_time'],
                                'total_capacity' => $slot['capacity'],
                                'booked_slots' => 0,
                                'price_per_slot' => $slot['price'],
                                'status' => 'available',
                                'notes' => null,
                            ]);

                            $createdCount++;
                        }
                    }
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "{$createdCount} schedules created successfully.",
                'count' => $createdCount,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Error creating schedules: ' . $e->getMessage(),
            ], 500);
        }
    }
}