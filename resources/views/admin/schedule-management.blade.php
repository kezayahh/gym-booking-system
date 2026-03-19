{{-- File: resources/views/admin/schedule-management.blade.php --}}
@extends('layouts.admin')
@section('title', 'Schedule Management - City Gymnasium')

@section('content')
<div class="mb-6">
    <h1 class="text-3xl font-bold text-gray-800">
        <i class="fas fa-calendar-alt text-blue-500 mr-2"></i>Schedule Management
    </h1>
    <p class="text-gray-600 mt-1">Create and manage gym schedules</p>
</div>

{{-- Statistics Cards --}}
<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
    <div class="bg-white rounded-lg shadow-lg p-6">
        <div class="flex items-center">
            <div class="bg-blue-100 rounded-full p-3 mr-4">
                <i class="fas fa-calendar text-blue-600 text-2xl"></i>
            </div>
            <div>
                <p class="text-gray-500 text-sm">Total Schedules</p>
                <h3 class="text-2xl font-bold text-gray-800">{{ $totalSchedules }}</h3>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-lg p-6">
        <div class="flex items-center">
            <div class="bg-green-100 rounded-full p-3 mr-4">
                <i class="fas fa-check-circle text-green-600 text-2xl"></i>
            </div>
            <div>
                <p class="text-gray-500 text-sm">Available</p>
                <h3 class="text-2xl font-bold text-gray-800">{{ $availableSchedules }}</h3>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-lg p-6">
        <div class="flex items-center">
            <div class="bg-red-100 rounded-full p-3 mr-4">
                <i class="fas fa-times-circle text-red-600 text-2xl"></i>
            </div>
            <div>
                <p class="text-gray-500 text-sm">Full</p>
                <h3 class="text-2xl font-bold text-gray-800">{{ $fullSchedules }}</h3>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-lg p-6">
        <div class="flex items-center">
            <div class="bg-purple-100 rounded-full p-3 mr-4">
                <i class="fas fa-users text-purple-600 text-2xl"></i>
            </div>
            <div>
                <p class="text-gray-500 text-sm">Total Capacity</p>
                <h3 class="text-2xl font-bold text-gray-800">{{ $totalCapacity }}</h3>
            </div>
        </div>
    </div>
</div>

{{-- Action Buttons & Filters --}}
<div class="bg-white rounded-lg shadow-lg p-6 mb-6">
    <div class="flex flex-wrap items-center justify-between gap-4 mb-4">
        <div class="flex gap-3">
            <button onclick="openCreateModal()" 
                    class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-semibold shadow-lg">
                <i class="fas fa-plus mr-2"></i>Create Schedule
            </button>
            <button onclick="openBulkCreateModal()" 
                    class="px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition font-semibold shadow-lg">
                <i class="fas fa-calendar-plus mr-2"></i>Bulk Create
            </button>
        </div>
    </div>

    {{-- Filters --}}
    <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <input type="date" name="date" value="{{ request('date') }}" placeholder="Search by date"
               class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
        
        <select name="status" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
            <option value="">All Status</option>
            <option value="available" {{ request('status') === 'available' ? 'selected' : '' }}>Available</option>
            <option value="full" {{ request('status') === 'full' ? 'selected' : '' }}>Full</option>
            <option value="closed" {{ request('status') === 'closed' ? 'selected' : '' }}>Closed</option>

        </select>

        <button type="submit" class="px-6 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition font-semibold">
            <i class="fas fa-search mr-2"></i>Filter
        </button>

        <a href="{{ route('admin.schedules') }}" class="px-6 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition font-semibold text-center">
            <i class="fas fa-redo mr-2"></i>Reset
        </a>
    </form>
</div>

{{-- Schedules Table --}}
<div class="bg-white rounded-lg shadow-lg overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time Slot</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Capacity</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Price / Hour</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($schedules as $schedule)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-medium text-gray-900">
                            {{ $schedule->date->format('M d, Y') }}
                        </div>
                        <div class="text-xs text-gray-500">
                            {{ $schedule->date->format('l') }}
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        {{ $schedule->time_slot }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-900">
                            {{ $schedule->total_capacity }}
                        </div>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-semibold text-gray-900">
                                {{ $schedule->total_capacity }}
                            </div>
                        </td>

                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-bold text-gray-900">
                            ₱{{ number_format($schedule->price_per_slot, 2) }}/hour
                        </div>
                        <div class="text-xs text-gray-500">
                            {{ $schedule->duration_hours }} hour(s) • Total: 
                            ₱{{ number_format($schedule->total_price, 2) }}
                        </div>
                    </td>

                    <td class="px-6 py-4 whitespace-nowrap">
                        @if($schedule->status === 'available')
                            <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                Available
                            </span>
                        @elseif($schedule->status === 'full')
                            <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                Full
                            </span>
                        @else
                            <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                                {{ ucfirst($schedule->status) }}
                            </span>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                        <div class="flex space-x-2">
                            <button onclick="viewSchedule({{ $schedule->id }})"
                                    class="text-blue-600 hover:text-blue-900" title="View">
                                <i class="fas fa-eye"></i>
                            </button>
                            @if($schedule->booked_slots == 0)
                            <button onclick="editSchedule({{ $schedule->id }})" 
                                    class="text-green-600 hover:text-green-900" title="Edit">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button onclick="deleteSchedule({{ $schedule->id }})" 
                                    class="text-red-600 hover:text-red-900" title="Delete">
                                <i class="fas fa-trash"></i>
                            </button>
                            @else
                            <span class="text-gray-400" title="Cannot edit/delete - has bookings">
                                <i class="fas fa-lock"></i>
                            </span>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                        <i class="fas fa-inbox text-6xl mb-4 text-gray-300"></i>
                        <p class="text-lg font-semibold mb-2">No Schedules Found</p>
                        <p class="text-sm">Create your first schedule to get started</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($schedules->hasPages())
    <div class="p-6 border-t border-gray-200">
        {{ $schedules->links() }}
    </div>
    @endif
</div>

{{-- Create Schedule Modal --}}
<div id="createModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-xl font-bold text-gray-800">Create New Schedule</h3>
            <button onclick="closeCreateModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>

        <form id="createScheduleForm" class="space-y-4">
            @csrf
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Date *</label>
                    <input type="date" name="date" required min="{{ date('Y-m-d') }}"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Total Capacity *</label>
                    <input type="number" name="total_capacity" required min="1" value="20"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Start Time *</label>
                    <input type="time" name="start_time" required
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">End Time *</label>
                    <input type="time" name="end_time" required
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>

                <div class="col-span-2">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Price per Hour *</label>
                    <input type="number" name="price_per_slot" required min="0" step="0.01" value="100"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>

                <div class="col-span-2">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Notes (Optional)</label>
                    <textarea name="notes" rows="3"
                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                              placeholder="Any special notes..."></textarea>
                </div>
            </div>

            <div class="flex space-x-3 pt-4">
                <button type="submit" 
                        class="flex-1 px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-semibold">
                    <i class="fas fa-save mr-2"></i>Create Schedule
                </button>
                <button type="button" onclick="closeCreateModal()" 
                        class="px-6 py-3 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition font-semibold">
                    Cancel
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Edit Schedule Modal --}}
<div id="editModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-xl font-bold text-gray-800">Edit Schedule</h3>
            <button onclick="closeEditModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>

        <form id="editScheduleForm" class="space-y-4">
            @csrf
            {{-- store id here --}}
            <input type="hidden" name="id" id="edit_schedule_id">

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Date *</label>
                    <input type="date" name="date" id="edit_date" required
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Total Capacity *</label>
                    <input type="number" name="total_capacity" id="edit_total_capacity" required min="1"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Start Time *</label>
                    <input type="time" name="start_time" id="edit_start_time" required
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">End Time *</label>
                    <input type="time" name="end_time" id="edit_end_time" required
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>

                <div class="col-span-2">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Price per Hour *</label>
                    <input type="number" name="price_per_slot" id="edit_price_per_slot" required min="0" step="0.01"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>

                <div class="col-span-2">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Notes (Optional)</label>
                    <textarea name="notes" id="edit_notes" rows="3"
                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                              placeholder="Any special notes..."></textarea>
                </div>
            </div>

            <div class="flex space-x-3 pt-4">
                <button type="submit" 
                        class="flex-1 px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition font-semibold">
                    <i class="fas fa-save mr-2"></i>Update Schedule
                </button>
                <button type="button" onclick="closeEditModal()" 
                        class="px-6 py-3 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition font-semibold">
                    Cancel
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Bulk Create Modal --}}
<div id="bulkCreateModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg shadow-xl max-w-4xl w-full p-6 max-h-screen overflow-y-auto">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-xl font-bold text-gray-800">Bulk Create Schedules</h3>
            <button onclick="closeBulkCreateModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        <p class="text-sm text-gray-600 mb-4">Create multiple schedules for selected days and time slots</p>
        
        <form id="bulkCreateForm" class="space-y-4">
            @csrf
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Start Date *</label>
                    <input type="date" name="start_date" required min="{{ date('Y-m-d') }}"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">End Date *</label>
                    <input type="date" name="end_date" required min="{{ date('Y-m-d') }}"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Select Days *</label>
                <div class="grid grid-cols-4 gap-2">
                    @foreach(['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'] as $index => $day)
                    <label class="flex items-center space-x-2 p-2 border rounded hover:bg-gray-50 cursor-pointer">
                        <input type="checkbox" name="days_of_week[]" value="{{ $index }}" class="form-checkbox">
                        <span class="text-sm">{{ $day }}</span>
                    </label>
                    @endforeach
                </div>
            </div>

            <div id="timeSlotContainer">
                <label class="block text-sm font-semibold text-gray-700 mb-2">Time Slots *</label>
                <div class="time-slot-row grid grid-cols-4 gap-2 mb-2">
                    <input type="time" name="time_slots[0][start_time]" required placeholder="Start Time"
                           class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    <input type="time" name="time_slots[0][end_time]" required placeholder="End Time"
                           class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    <input type="number" name="time_slots[0][capacity]" required min="1" placeholder="Capacity" value="20"
                           class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    <input type="number" name="time_slots[0][price]" required min="0" step="0.01" placeholder="Price per Hour" value="100"
                           class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>
            </div>
            <button type="button" onclick="addTimeSlot()" class="text-blue-600 hover:text-blue-700 text-sm font-semibold">
                <i class="fas fa-plus mr-1"></i>Add Another Time Slot
            </button>

            <div class="flex space-x-3 pt-4">
                <button type="submit" 
                        class="flex-1 px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition font-semibold">
                    <i class="fas fa-calendar-plus mr-2"></i>Create Schedules
                </button>
                <button type="button" onclick="closeBulkCreateModal()" 
                        class="px-6 py-3 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition font-semibold">
                    Cancel
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
let timeSlotCounter = 1;

// Open/Close Modals
function openCreateModal() {
    document.getElementById('createModal').classList.remove('hidden');
}

function closeCreateModal() {
    document.getElementById('createModal').classList.add('hidden');
    document.getElementById('createScheduleForm').reset();
}

function openBulkCreateModal() {
    document.getElementById('bulkCreateModal').classList.remove('hidden');
}

function closeBulkCreateModal() {
    document.getElementById('bulkCreateModal').classList.add('hidden');
    document.getElementById('bulkCreateForm').reset();
}

// Edit modal open/close
function openEditModal() {
    document.getElementById('editModal').classList.remove('hidden');
}

function closeEditModal() {
    document.getElementById('editModal').classList.add('hidden');
    document.getElementById('editScheduleForm').reset();
    document.getElementById('edit_schedule_id').value = '';
}

// Add Time Slot
function addTimeSlot() {
    const container = document.getElementById('timeSlotContainer');
    const newRow = document.createElement('div');
    newRow.className = 'time-slot-row grid grid-cols-4 gap-2 mb-2';
    newRow.innerHTML = `
        <input type="time" name="time_slots[${timeSlotCounter}][start_time]" required placeholder="Start Time"
               class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
        <input type="time" name="time_slots[${timeSlotCounter}][end_time]" required placeholder="End Time"
               class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
        <input type="number" name="time_slots[${timeSlotCounter}][capacity]" required min="1" placeholder="Capacity" value="20"
               class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
        <input type="number" name="time_slots[${timeSlotCounter}][price]" required min="0" step="0.01" placeholder="Price per Hour" value="100"
               class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
    `;
    container.appendChild(newRow);
    timeSlotCounter++;
}

// Create Schedule
document.getElementById('createScheduleForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const data = Object.fromEntries(formData);
    
            fetch('/admin/schedules/store', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify(data)
        })

    .then(response => response.json())
    .then(result => {
        if (result.success) {
            alert(result.message);
            location.reload();
        } else {
            alert(result.message);
        }
    })
    .catch(error => {
        alert('An error occurred. Please try again.');
        console.error(error);
    });
});

// Bulk Create
document.getElementById('bulkCreateForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    
    const data = {
        start_date: formData.get('start_date'),
        end_date: formData.get('end_date'),
        days_of_week: formData.getAll('days_of_week[]').map(Number),
        time_slots: []
    };
    
    const timeSlots = document.querySelectorAll('.time-slot-row');
    timeSlots.forEach((row, index) => {
        data.time_slots.push({
            start_time: formData.get(`time_slots[${index}][start_time]`),
            end_time: formData.get(`time_slots[${index}][end_time]`),
            capacity: parseInt(formData.get(`time_slots[${index}][capacity]`)),
            price: parseFloat(formData.get(`time_slots[${index}][price]`))
        });
    });
    
    fetch('/admin/schedules/bulk-create', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            alert(result.message);
            location.reload();
        } else {
            alert(result.message);
        }
    })
    .catch(error => {
        alert('An error occurred. Please try again.');
        console.error(error);
    });
});

// Delete Schedule
function deleteSchedule(id) {
    if (!confirm('Are you sure you want to delete this schedule?')) return;
    
    fetch(`/admin/schedules/${id}/delete`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
             'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            alert(result.message);
            location.reload();
        } else {
            alert(result.message);
        }
    })
    .catch(error => {
        alert('An error occurred. Please try again.');
        console.error(error);
    });
}

// View Schedule – still goes to page/JSON (your current behavior)
function viewSchedule(id) {
    window.location.href = `/admin/schedules/${id}`;
}

// Edit Schedule – load JSON, fill modal, submit to /update
function editSchedule(id) {
    fetch(`/admin/schedules/${id}`)
        .then(response => response.json())
        .then(result => {
            const s = result.schedule || result;

            // date: ensure it's in YYYY-MM-DD format
            let dateValue = s.date;
            if (typeof dateValue === 'string' && dateValue.length > 10) {
                dateValue = dateValue.substring(0, 10);
            }

            // times: trim to HH:MM for <input type="time">
            const startTime = (s.start_time || s.start || '').substring(0, 5);
            const endTime   = (s.end_time   || s.end   || '').substring(0, 5);

            document.getElementById('edit_schedule_id').value = s.id;
            document.getElementById('edit_date').value = dateValue || '';
            document.getElementById('edit_total_capacity').value = s.total_capacity;
            document.getElementById('edit_start_time').value = startTime;
            document.getElementById('edit_end_time').value = endTime;
            document.getElementById('edit_price_per_slot').value = s.price_per_slot;
            document.getElementById('edit_notes').value = s.notes ?? '';

            openEditModal();
        })
        .catch(error => {
            console.error(error);
            alert('An error occurred while loading the schedule.');
        });
}

// Submit Edit Schedule form
document.getElementById('editScheduleForm').addEventListener('submit', function(e) {
    e.preventDefault();

    const id = document.getElementById('edit_schedule_id').value;
    const formData = new FormData(e.target);
    const data = Object.fromEntries(formData);

    fetch(`/admin/schedules/${id}/update`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
              'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            alert(result.message);
            location.reload();
        } else {
            alert(result.message || 'Failed to update schedule.');
        }
    })
    .catch(error => {
        console.error(error);
        alert('An error occurred. Please try again.');
    });
});
</script>
@endpush
