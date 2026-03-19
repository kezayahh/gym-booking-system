<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Schedule;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ScheduleManagementController extends Controller
{
    /**
     * Display all schedules
     */
    public function index(Request $request)
    {
        $query = Schedule::query();
        
        // Search by date
        if ($request->filled('date')) {
            $query->whereDate('date', $request->date);
        }
        
        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        
        // Date range filter
        if ($request->filled('start_date')) {
            $query->where('date', '>=', $request->start_date);
        }
        
        if ($request->filled('end_date')) {
            $query->where('date', '<=', $request->end_date);
        }
        
        // Get schedules with bookings count
        $schedules = $query->withCount('bookings')
            ->orderBy('date', 'desc')
            ->orderBy('start_time', 'asc')
            ->paginate(15);
        
        // Statistics
        $totalSchedules = Schedule::count();
        $availableSchedules = Schedule::where('status', 'available')
            ->where('date', '>=', Carbon::today())
            ->count();
        $fullSchedules = Schedule::where('status', 'full')->count();
        $totalCapacity = Schedule::where('date', '>=', Carbon::today())->sum('total_capacity');
        
        return view('admin.schedule-management', compact(
            'schedules',
            'totalSchedules',
            'availableSchedules',
            'fullSchedules',
            'totalCapacity'
        ));
    }
    
    /**
     * Store new schedule
     */
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
        
        // Check for overlapping schedules
        $overlapping = Schedule::where('date', $validated['date'])
            ->where(function($query) use ($validated) {
                $query->whereBetween('start_time', [$validated['start_time'], $validated['end_time']])
                      ->orWhereBetween('end_time', [$validated['start_time'], $validated['end_time']])
                      ->orWhere(function($q) use ($validated) {
                          $q->where('start_time', '<=', $validated['start_time'])
                            ->where('end_time', '>=', $validated['end_time']);
                      });
            })
            ->exists();
        
        if ($overlapping) {
            return response()->json([
                'success' => false,
                'message' => 'This time slot overlaps with an existing schedule!'
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
        
        // Log activity
        activity()
            ->causedBy(auth()->user())
            ->performedOn($schedule)
            ->log('Created new schedule for ' . Carbon::parse($validated['date'])->format('M d, Y'));
        
        return response()->json([
            'success' => true,
            'message' => 'Schedule created successfully!',
            'schedule' => $schedule
        ]);
    }
    
    /**
     * Show single schedule
     */
 // app/Http/Controllers/Admin/ScheduleManagementController.php

public function show($id)
{
    $schedule = Schedule::findOrFail($id);

    return response()->json([
        'success'  => true,
        'schedule' => $schedule,
    ]);
}


    
    /**
     * Update schedule
     */
    public function update(Request $request, $id)
    {
        $schedule = Schedule::findOrFail($id);
        
        // Don't allow updating if there are bookings
        if ($schedule->booked_slots > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot update schedule with existing bookings!'
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
        
        $schedule->update($validated);
        
        // Log activity
        activity()
            ->causedBy(auth()->user())
            ->performedOn($schedule)
            ->log('Updated schedule for ' . $schedule->date->format('M d, Y'));
        
        return response()->json([
            'success' => true,
            'message' => 'Schedule updated successfully!',
            'schedule' => $schedule
        ]);
    }
    
    /**
     * Delete schedule
     */
    public function destroy($id)
    {
        $schedule = Schedule::findOrFail($id);
        
        // Don't allow deleting if there are bookings
        if ($schedule->booked_slots > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete schedule with existing bookings!'
            ], 422);
        }
        
        // Log activity
        activity()
            ->causedBy(auth()->user())
            ->performedOn($schedule)
            ->log('Deleted schedule for ' . $schedule->date->format('M d, Y'));
        
        $schedule->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Schedule deleted successfully!'
        ]);
    }
    
    /**
     * Bulk create schedules (for recurring time slots)
     */
    public function bulkCreate(Request $request)
    {
        $validated = $request->validate([
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after:start_date',
            'time_slots' => 'required|array|min:1',
            'time_slots.*.start_time' => 'required|date_format:H:i',
            'time_slots.*.end_time' => 'required|date_format:H:i|after:time_slots.*.start_time',
            'time_slots.*.capacity' => 'required|integer|min:1',
            'time_slots.*.price' => 'required|numeric|min:0',
            'days_of_week' => 'required|array|min:1',
            'days_of_week.*' => 'integer|min:0|max:6', // 0=Sunday, 6=Saturday
        ]);
        
        $startDate = Carbon::parse($validated['start_date']);
        $endDate = Carbon::parse($validated['end_date']);
        $daysOfWeek = $validated['days_of_week'];
        $createdCount = 0;
        
        DB::beginTransaction();
        
        try {
            // Loop through each day in range
            for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
                // Check if this day of week is selected
                if (in_array($date->dayOfWeek, $daysOfWeek)) {
                    // Create each time slot for this day
                    foreach ($validated['time_slots'] as $slot) {
                        // Check for overlaps
                        $overlapping = Schedule::where('date', $date->format('Y-m-d'))
                            ->where(function($query) use ($slot) {
                                $query->whereBetween('start_time', [$slot['start_time'], $slot['end_time']])
                                      ->orWhereBetween('end_time', [$slot['start_time'], $slot['end_time']]);
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
                            ]);
                            $createdCount++;
                        }
                    }
                }
            }
            
            DB::commit();
            
            // Log activity
            activity()
                ->causedBy(auth()->user())
                ->log("Bulk created {$createdCount} schedules");
            
            return response()->json([
                'success' => true,
                'message' => "{$createdCount} schedules created successfully!",
                'count' => $createdCount
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Error creating schedules: ' . $e->getMessage()
            ], 500);
        }
    }
}