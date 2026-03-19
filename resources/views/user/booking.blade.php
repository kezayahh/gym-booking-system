{{-- File: resources/views/user/booking.blade.php --}}

@extends('layouts.user')

@section('title', 'My Bookings - City Gymnasium')

@section('content')
<div class="mb-6 flex items-center justify-between">
    <div>
        <h1 class="text-3xl font-bold text-gray-800">
            <i class="fas fa-clipboard-list text-blue-500 mr-2"></i>My Bookings
        </h1>
        <p class="text-gray-600 mt-1">View and manage your gym bookings</p>
    </div>
    <a href="{{ route('user.schedule') }}" class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-semibold shadow-lg">
        <i class="fas fa-plus mr-2"></i>New Booking
    </a>
</div>

{{-- Booking Stats --}}
<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
    <div class="bg-white rounded-lg shadow-lg p-6">
        <div class="flex items-center">
            <div class="bg-blue-100 rounded-full p-3 mr-4">
                <i class="fas fa-calendar-check text-blue-600 text-2xl"></i>
            </div>
            <div>
                <p class="text-gray-500 text-sm">Total Bookings</p>
                <h3 class="text-2xl font-bold text-gray-800">{{ $bookings->total() }}</h3>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-lg p-6">
        <div class="flex items-center">
            <div class="bg-yellow-100 rounded-full p-3 mr-4">
                <i class="fas fa-clock text-yellow-600 text-2xl"></i>
            </div>
            <div>
                <p class="text-gray-500 text-sm">Pending</p>
                <h3 class="text-2xl font-bold text-gray-800">{{ $bookings->where('status', 'pending')->count() }}</h3>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-lg p-6">
        <div class="flex items-center">
            <div class="bg-green-100 rounded-full p-3 mr-4">
                <i class="fas fa-check-circle text-green-600 text-2xl"></i>
            </div>
            <div>
                <p class="text-gray-500 text-sm">Confirmed</p>
                <h3 class="text-2xl font-bold text-gray-800">{{ $bookings->where('status', 'confirmed')->count() }}</h3>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-lg p-6">
        <div class="flex items-center">
            <div class="bg-red-100 rounded-full p-3 mr-4">
                <i class="fas fa-times-circle text-red-600 text-2xl"></i>
            </div>
            <div>
                <p class="text-gray-500 text-sm">Cancelled</p>
                <h3 class="text-2xl font-bold text-gray-800">{{ $bookings->where('status', 'cancelled')->count() }}</h3>
            </div>
        </div>
    </div>
</div>

{{-- Bookings Table --}}
<div class="bg-white rounded-lg shadow-lg overflow-hidden">
    <div class="p-6 border-b border-gray-200">
        <h3 class="text-xl font-bold text-gray-800">
            <i class="fas fa-list mr-2"></i>All Bookings
        </h3>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Booking #</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date & Time</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Slots</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Payment</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($bookings as $booking)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-medium text-gray-900">{{ $booking->booking_number }}</div>
                        <div class="text-xs text-gray-500">{{ $booking->created_at->format('M d, Y') }}</div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="text-sm text-gray-900">
                            <i class="fas fa-calendar text-blue-500 mr-1"></i>
                            {{ $booking->schedule->date->format('M d, Y') }}
                        </div>
                        <div class="text-xs text-gray-500">
                            <i class="fas fa-clock text-blue-500 mr-1"></i>
                            {{ $booking->schedule->time_slot }}
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="text-sm text-gray-900">
                            <i class="fas fa-users text-gray-400 mr-1"></i>
                            {{ $booking->number_of_slots }}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-bold text-gray-900">₱{{ number_format($booking->total_amount, 2) }}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        @if($booking->status === 'pending')
                            <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                <i class="fas fa-clock mr-1"></i>Pending
                            </span>
                        @elseif($booking->status === 'confirmed')
                            <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                <i class="fas fa-check-circle mr-1"></i>Confirmed
                            </span>
                        @elseif($booking->status === 'cancelled')
                            <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                <i class="fas fa-times-circle mr-1"></i>Cancelled
                            </span>
                        @elseif($booking->status === 'completed')
                            <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                <i class="fas fa-flag-checkered mr-1"></i>Completed
                            </span>
                        @else
                            <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                                {{ ucfirst($booking->status) }}
                            </span>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        @if($booking->isPaid)
                            <span class="text-xs text-green-600 font-semibold">
                                <i class="fas fa-check-circle mr-1"></i>Paid
                            </span>
                        @else
                            <span class="text-xs text-red-600 font-semibold">
                                <i class="fas fa-exclamation-circle mr-1"></i>Unpaid
                            </span>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                        <div class="flex items-center space-x-2">
                            @if(!$booking->isPaid && $booking->status === 'pending')
                                <a href="{{ route('user.payments') }}" 
                                   class="text-blue-600 hover:text-blue-900 font-semibold" 
                                   title="Pay Now">
                                    <i class="fas fa-credit-card mr-1"></i>Pay
                                </a>
                            @endif
                            
                            @if($booking->canCancel)
                                <button onclick="cancelBooking({{ $booking->id }}, '{{ $booking->booking_number }}')" 
                                        class="text-red-600 hover:text-red-900 font-semibold" 
                                        title="Cancel Booking">
                                    <i class="fas fa-times-circle mr-1"></i>Cancel
                                </button>
                            @endif
                            
                            <button onclick="viewBookingDetails({{ $booking->id }})" 
                                    class="text-gray-600 hover:text-gray-900" 
                                    title="View Details">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-6 py-12 text-center">
                        <div class="text-gray-500">
                            <i class="fas fa-inbox text-6xl mb-4 text-gray-300"></i>
                            <p class="text-lg font-semibold mb-2">No Bookings Yet</p>
                            <p class="text-sm mb-4">Start by creating your first booking!</p>
                            <a href="{{ route('user.schedule') }}" class="inline-block px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                                <i class="fas fa-plus mr-2"></i>Create Booking
                            </a>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($bookings->hasPages())
    <div class="p-6 border-t border-gray-200">
        {{ $bookings->links() }}
    </div>
    @endif
</div>

{{-- Quick Booking Modal --}}
<div id="quickBookModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-xl font-bold text-gray-800">Quick Booking</h3>
            <button onclick="closeQuickBookModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        
        <div id="quickBookDetails" class="mb-4 p-4 bg-blue-50 rounded-lg"></div>
        
        <form id="quickBookingForm" class="space-y-4">
            <input type="hidden" id="quick_schedule_id" name="schedule_id">
            
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                    Number of Slots
                </label>
                <input type="number" id="quick_slots" name="number_of_slots" min="1" value="1"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            </div>
            
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                    Special Requests (Optional)
                </label>
                <textarea id="quick_requests" name="special_requests" rows="3"
                          class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                          placeholder="Any special requirements..."></textarea>
            </div>
            
            <div class="flex items-center justify-between pt-4 border-t">
                <span class="text-lg font-bold text-gray-800">Total: ₱<span id="quick_total">0</span></span>
                <button type="submit" class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-semibold">
                    <i class="fas fa-check mr-2"></i>Confirm Booking
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Cancel Booking Modal --}}
<div id="cancelModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-xl font-bold text-gray-800">Cancel Booking</h3>
            <button onclick="closeCancelModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        
        <p class="text-gray-600 mb-4">Are you sure you want to cancel booking <strong id="cancel_booking_number"></strong>?</p>
        
        <form id="cancelBookingForm" class="space-y-4">
            <input type="hidden" id="cancel_booking_id">
            
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                    Cancellation Reason *
                </label>
                <textarea id="cancel_reason" name="reason" rows="3" required
                          class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent"
                          placeholder="Please provide a reason for cancellation..."></textarea>
            </div>
            
            <div class="flex space-x-3">
                <button type="button" onclick="closeCancelModal()" 
                        class="flex-1 px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                    No, Keep It
                </button>
                <button type="submit" 
                        class="flex-1 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition font-semibold">
                    <i class="fas fa-times mr-2"></i>Yes, Cancel
                </button>
            </div>
        </form>
    </div>
</div>
{{-- Booking Details Modal --}}
<div id="detailsModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg shadow-xl max-w-lg w-full p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-xl font-bold text-gray-800">Booking Details</h3>
            <button onclick="closeDetailsModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>

        <div id="detailsContent">
            <p class="text-center text-gray-500">Loading...</p>
        </div>

        <div class="flex justify-end mt-4">
            <button onclick="closeDetailsModal()" 
                class="px-4 py-2 bg-gray-200 rounded-lg hover:bg-gray-300 transition">
                Close
            </button>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
let quickBookPrice = 0;
let quickBookMaxSlots = 0;

// Quick Booking Modal Functions
function openQuickBookModal(scheduleId, date, timeSlot, price, availableSlots) {
    quickBookPrice = price;
    quickBookMaxSlots = availableSlots;
    
    document.getElementById('quick_schedule_id').value = scheduleId;
    document.getElementById('quickBookDetails').innerHTML = `
        <p class="text-sm text-gray-600 mb-2">${date}</p>
        <p class="text-lg font-bold text-gray-800">${timeSlot}</p>
        <p class="text-sm text-gray-600 mt-2">
            <i class="fas fa-users mr-1"></i>${availableSlots} slots available
        </p>
        <p class="text-sm text-blue-600 font-semibold mt-1">₱${price} per slot</p>
    `;
    
    document.getElementById('quick_slots').max = availableSlots;
    document.getElementById('quick_slots').value = 1;
    updateQuickBookTotal();
    
    document.getElementById('quickBookModal').classList.remove('hidden');
}

function closeQuickBookModal() {
    document.getElementById('quickBookModal').classList.add('hidden');
    document.getElementById('quickBookingForm').reset();
}

function updateQuickBookTotal() {
    const slots = parseInt(document.getElementById('quick_slots').value) || 1;
    const total = quickBookPrice * slots;
    document.getElementById('quick_total').textContent = total.toFixed(0);
}

document.getElementById('quick_slots').addEventListener('input', updateQuickBookTotal);

// Quick Booking Form Submission
document.getElementById('quickBookingForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const data = Object.fromEntries(formData);
    
    fetch('/user/bookings/store', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            alert('Booking created successfully! Redirecting to payment...');
            window.location.href = '/user/payments';
        } else {
            alert(result.message || 'An error occurred');
        }
    })
    .catch(error => {
        alert('An error occurred. Please try again.');
        console.error(error);
    });
});

// Cancel Booking Functions
function cancelBooking(bookingId, bookingNumber) {
    document.getElementById('cancel_booking_id').value = bookingId;
    document.getElementById('cancel_booking_number').textContent = bookingNumber;
    document.getElementById('cancelModal').classList.remove('hidden');
}

function closeCancelModal() {
    document.getElementById('cancelModal').classList.add('hidden');
    document.getElementById('cancelBookingForm').reset();
}

// Cancel Booking Form Submission
document.getElementById('cancelBookingForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const bookingId = document.getElementById('cancel_booking_id').value;
    const reason = document.getElementById('cancel_reason').value;
    
    if (!reason.trim()) {
        alert('Please provide a cancellation reason');
        return;
    }
    
    fetch(`/user/bookings/${bookingId}/cancel`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({ reason: reason })
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            alert('Booking cancelled successfully!');
            window.location.reload();
        } else {
            alert(result.message || 'An error occurred');
        }
    })
    .catch(error => {
        alert('An error occurred. Please try again.');
        console.error(error);
    });
});

function viewBookingDetails(bookingId) {
    // Show modal immediately
    document.getElementById('detailsModal').classList.remove('hidden');
    document.getElementById('detailsContent').innerHTML = `
        <p class="text-center text-gray-500">Loading...</p>
    `;

    fetch(`/user/bookings/${bookingId}/details`, {
        method: 'GET',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            const b = result.data;

            document.getElementById('detailsContent').innerHTML = `
                <div class="space-y-3">
                    <p><strong>Booking #:</strong> ${b.booking_number}</p>
                    <p><strong>Date:</strong> ${b.date}</p>
                    <p><strong>Time:</strong> ${b.time}</p>
                    <p><strong>Slots:</strong> ${b.slots}</p>
                    <p><strong>Total Amount:</strong> ₱${Number(b.total).toLocaleString()}</p>
                    <p><strong>Status:</strong> ${b.status}</p>
                    <p><strong>Payment:</strong> ${b.payment}</p>
                    ${b.requests ? `<p><strong>Special Requests:</strong> ${b.requests}</p>` : ''}
                </div>
            `;
        } else {
            document.getElementById('detailsContent').innerHTML = `
                <p class="text-center text-red-600 font-semibold">
                    Unable to load booking details.
                </p>
            `;
        }
    })
    .catch(error => {
        console.error(error);
        document.getElementById('detailsContent').innerHTML = `
            <p class="text-center text-red-600">Error loading booking details.</p>
        `;
    });
}

function closeDetailsModal() {
    document.getElementById('detailsModal').classList.add('hidden');
}

</script>
@endpush