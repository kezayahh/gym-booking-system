{{-- File: resources/views/user/schedule.blade.php --}}

@extends('layouts.user')

@section('title', 'Schedule - City Gymnasium')

@section('content')
<div class="mb-6">
    <h1 class="text-3xl font-bold text-gray-800">
        <i class="fas fa-calendar-alt text-blue-500 mr-2"></i>Schedule & Availability
    </h1>
    <p class="text-gray-600 mt-1">View available time slots and book your gym session</p>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    
    {{-- Calendar Section (2/3 width) --}}
    <div class="lg:col-span-2">
        <div class="bg-white rounded-lg shadow-lg p-6">
            {{-- Calendar Controls --}}
            <div class="flex items-center justify-between mb-6">
                <button id="prevMonth" class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition">
                    <i class="fas fa-chevron-left"></i>
                </button>
                <h2 id="monthYear" class="text-2xl font-bold text-gray-800"></h2>
                <button id="nextMonth" class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition">
                    <i class="fas fa-chevron-right"></i>
                </button>
            </div>

            {{-- Calendar Grid --}}
            <div class="calendar-container">
                <div class="grid grid-cols-7 gap-2 mb-2">
                    <div class="text-center font-semibold text-gray-600 py-2">Sun</div>
                    <div class="text-center font-semibold text-gray-600 py-2">Mon</div>
                    <div class="text-center font-semibold text-gray-600 py-2">Tue</div>
                    <div class="text-center font-semibold text-gray-600 py-2">Wed</div>
                    <div class="text-center font-semibold text-gray-600 py-2">Thu</div>
                    <div class="text-center font-semibold text-gray-600 py-2">Fri</div>
                    <div class="text-center font-semibold text-gray-600 py-2">Sat</div>
                </div>
                <div id="calendar" class="grid grid-cols-7 gap-2"></div>
            </div>

            {{-- Legend --}}
            <div class="mt-6 flex items-center justify-center space-x-6 text-sm">
                <div class="flex items-center">
                    <span class="w-4 h-4 bg-green-500 rounded mr-2"></span>
                    <span class="text-gray-600">Available</span>
                </div>
                <div class="flex items-center">
                    <span class="w-4 h-4 bg-red-500 rounded mr-2"></span>
                    <span class="text-gray-600">Full</span>
                </div>
                <div class="flex items-center">
                    <span class="w-4 h-4 bg-gray-300 rounded mr-2"></span>
                    <span class="text-gray-600">Past</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Time Slots Section (1/3 width) --}}
    <div>
        <div class="bg-white rounded-lg shadow-lg p-6">
            <h3 class="text-xl font-bold text-gray-800 mb-4">
                <i class="fas fa-clock text-blue-500 mr-2"></i>Available Slots
            </h3>
            <p id="selectedDateDisplay" class="text-sm text-gray-600 mb-4">Select a date to view slots</p>
            
            <div id="timeSlotsContainer" class="space-y-3 max-h-96 overflow-y-auto">
                <div class="text-center text-gray-500 py-8">
                    <i class="fas fa-calendar-day text-4xl mb-2 text-gray-300"></i>
                    <p class="text-sm">Click on a date to see available time slots</p>
                </div>
            </div>
        </div>

        {{-- Quick Stats --}}
        <div class="bg-gradient-to-br from-blue-500 to-purple-600 rounded-lg shadow-lg p-6 mt-6 text-white">
            <h4 class="text-lg font-bold mb-3">
                <i class="fas fa-info-circle mr-2"></i>Quick Info
            </h4>
            <div class="space-y-2 text-sm">
                <p><i class="fas fa-clock mr-2"></i>Operating Hours: 8:00 AM - 10:00 PM</p>
                <p><i class="fas fa-users mr-2"></i>Capacity depends on schedule</p>
                <p><i class="fas fa-peso-sign mr-2"></i>Price: ₱100 per hour (sample)</p>
                <p><i class="fas fa-calendar-check mr-2"></i>Book up to 30 days in advance</p>
            </div>
        </div>
    </div>
</div>

{{-- Upcoming Available Slots --}}
<div class="mt-8 bg-white rounded-lg shadow-lg p-6">
    <h3 class="text-xl font-bold text-gray-800 mb-4">
        <i class="fas fa-star text-yellow-500 mr-2"></i>Upcoming Available Slots (Next 7 Days)
    </h3>
    
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        @forelse($upcomingSchedules as $schedule)
        <div class="border border-gray-200 rounded-lg p-4 hover:border-blue-500 hover:shadow-md transition cursor-pointer" 
             onclick="bookSchedule({{ $schedule->id }})">
            <div class="flex items-start justify-between mb-2">
                <div>
                    <p class="text-sm text-gray-500">{{ $schedule->date->format('l') }}</p>
                    <h4 class="text-lg font-bold text-gray-800">{{ $schedule->date->format('M d, Y') }}</h4>
                </div>
                {{-- Removed number of slots pill --}}
                <span class="px-3 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                    Available
                </span>
            </div>
            <div class="flex items-center text-gray-600 text-sm mb-2">
                <i class="fas fa-clock mr-2 text-blue-500"></i>
                <span>{{ $schedule->time_slot }}</span>
            </div>
            <div class="flex items-center justify-between">
                <span class="text-blue-600 font-bold">₱{{ number_format($schedule->price_per_slot, 0) }}/hour</span>
                <button class="px-4 py-2 bg-blue-500 text-white rounded-lg text-sm hover:bg-blue-600 transition">
                    <i class="fas fa-plus mr-1"></i>Book Now
                </button>
            </div>
        </div>
        @empty
        <div class="col-span-3 text-center py-8 text-gray-500">
            <i class="fas fa-calendar-times text-4xl mb-2 text-gray-300"></i>
            <p>No available slots in the next 7 days</p>
        </div>
        @endforelse
    </div>
</div>

{{-- Booking Modal --}}
<div id="bookingModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-10 mx-auto p-5 border w-full max-w-2xl shadow-lg rounded-lg bg-white">
        
        {{-- STEP 1: Booking Form --}}
        <div id="bookingStep" class="mt-3">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-xl font-bold text-gray-900">
                    <i class="fas fa-calendar-check text-blue-500 mr-2"></i>Book Your Session
                </h3>
                <button onclick="closeBookingModal()" class="text-gray-400 hover:text-gray-500 transition">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            {{-- Schedule Details --}}
            <div class="bg-blue-50 p-4 rounded-lg mb-4">
                <div id="modalScheduleDetails" class="space-y-2"></div>
            </div>

            <form id="bookingForm" class="space-y-4">
                {{-- REMOVED: Number of Slots field (we assume 1 per booking) --}}

                {{-- Payment Method Selection --}}
                <div>
                    <label for="modal_payment_method" class="block text-sm font-medium text-gray-700 mb-2">
                        Payment Method <span class="text-red-500">*</span>
                    </label>
                    <select 
                        id="modal_payment_method" 
                        name="payment_method"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        required
                    >
                        <option value="">Select payment method</option>
                        <option value="cash">💵 Cash (Pay at Gym)</option>
                        <option value="gcash">📱 GCash</option>
                        <option value="maya">💳 Maya (PayMaya)</option>
                        <option value="bank_transfer">🏦 Bank Transfer</option>
                    </select>
                </div>

                {{-- Special Requests --}}
                <div>
                    <label for="modal_special_requests" class="block text-sm font-medium text-gray-700 mb-1">
                        Special Requests (Optional)
                    </label>
                    <textarea 
                        id="modal_special_requests" 
                        name="special_requests"
                        rows="3"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        placeholder="Any special requirements or notes..."
                    ></textarea>
                </div>

                {{-- Total Amount --}}
                <div class="bg-gradient-to-r from-green-50 to-blue-50 p-4 rounded-lg">
                    <div class="flex justify-between items-center">
                        <span class="text-sm font-medium text-gray-700">Total Amount:</span>
                        <span id="modal_total" class="text-2xl font-bold text-green-600">₱0.00</span>
                    </div>
                </div>

                {{-- Hidden Schedule ID --}}
                <input type="hidden" id="modal_schedule_id" name="schedule_id">

                <div class="flex space-x-3 pt-4">
                    <button 
                        type="button"
                        onclick="closeBookingModal()" 
                        class="flex-1 px-4 py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition font-medium"
                    >
                        Cancel
                    </button>
                    <button 
                        type="submit"
                        class="flex-1 px-4 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-medium"
                    >
                        <i class="fas fa-arrow-right mr-2"></i>Continue to Payment
                    </button>
                </div>
            </form>
        </div>

        {{-- STEP 2: Payment Instructions --}}
        <div id="paymentStep" class="hidden mt-3">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-xl font-bold text-gray-900">
                    <i class="fas fa-credit-card text-green-500 mr-2"></i>Payment Instructions
                </h3>
                <button onclick="closeBookingModal()" class="text-gray-400 hover:text-gray-500 transition">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            {{-- Success Message --}}
            <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-4">
                <div class="flex items-start">
                    <i class="fas fa-check-circle text-green-500 text-2xl mr-3 mt-1"></i>
                    <div>
                        <h4 class="font-bold text-green-800 text-lg">Booking Created Successfully!</h4>
                        <p class="text-green-700 text-sm mt-1">Booking Number: <span id="confirmBookingNumber" class="font-mono font-bold"></span></p>
                    </div>
                </div>
            </div>

            {{-- Booking Summary --}}
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
                <h4 class="font-bold text-gray-800 mb-3">
                    <i class="fas fa-receipt text-blue-500 mr-2"></i>Booking Summary
                </h4>
                <div id="bookingSummary" class="space-y-2 text-sm"></div>
            </div>

            {{-- Payment Instructions (Dynamic) --}}
            <div id="paymentInstructions" class="mb-6"></div>

            <div class="flex space-x-3">
                <button 
                    onclick="goToPayments()" 
                    class="flex-1 px-4 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-medium"
                >
                    <i class="fas fa-wallet mr-2"></i>Go to My Payments
                </button>
                <button 
                    onclick="closeBookingModal()" 
                    class="flex-1 px-4 py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition font-medium"
                >
                    Close
                </button>
            </div>
        </div>
    </div>
</div>

<script>
let currentBookingData = null;

// Handle booking form submission
document.getElementById('bookingForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const scheduleId = document.getElementById('modal_schedule_id').value;
    const paymentMethod = document.getElementById('modal_payment_method').value;
    const specialRequests = document.getElementById('modal_special_requests').value;
    
    // Validation
    if (!scheduleId) {
        alert('Please select a schedule');
        return;
    }
    
    if (!paymentMethod) {
        alert('Please select a payment method');
        return;
    }
    
    // We now assume 1 slot per booking
    const slots = 1;
    
    const data = {
        schedule_id: parseInt(scheduleId),
        number_of_slots: slots,
        payment_method: paymentMethod,
        special_requests: specialRequests || null
    };
    
    const submitBtn = e.target.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Processing...';
    
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
            currentBookingData = result.booking;
            currentBookingData.payment_method = paymentMethod;
            currentBookingData.schedule = selectedSchedule;
            showPaymentStep();
        } else {
            alert(result.message || 'An error occurred. Please try again.');
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        }
    })
    .catch(error => {
        console.error('Fetch error:', error);
        alert('An error occurred. Please check your connection and try again.');
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
    });
});

function showPaymentStep() {
    document.getElementById('bookingStep').classList.add('hidden');
    document.getElementById('paymentStep').classList.remove('hidden');
    
    document.getElementById('confirmBookingNumber').textContent = currentBookingData.booking_number;
    
    document.getElementById('bookingSummary').innerHTML = `
        <div class="flex justify-between">
            <span class="text-gray-600">Date:</span>
            <span class="font-semibold">${currentBookingData.schedule.date}</span>
        </div>
        <div class="flex justify-between">
            <span class="text-gray-600">Time:</span>
            <span class="font-semibold">${currentBookingData.schedule.time_slot}</span>
        </div>
        <div class="flex justify-between">
            <span class="text-gray-600">Payment Method:</span>
            <span class="font-semibold">${getPaymentMethodName(currentBookingData.payment_method)}</span>
        </div>
        <div class="flex justify-between pt-2 border-t border-blue-300">
            <span class="text-gray-800 font-bold">Total Amount:</span>
            <span class="text-lg font-bold text-green-600">₱${parseFloat(currentBookingData.total_amount).toLocaleString('en-PH', {minimumFractionDigits: 2})}</span>
        </div>
    `;
    
    const paymentMethod = currentBookingData.payment_method;
    let instructionsHTML = '';

    // (Payment instructions unchanged, just uses total_amount)
    if (paymentMethod === 'cash') {
        instructionsHTML = `
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                <h4 class="font-bold text-yellow-800 mb-3 flex items-center">
                    <i class="fas fa-money-bill-wave text-yellow-600 mr-2"></i>Cash Payment Instructions
                </h4>
                <div class="space-y-2 text-sm text-yellow-900">
                    <p>✅ Your booking has been confirmed!</p>
                    <p>💵 Please pay <strong>₱${parseFloat(currentBookingData.total_amount).toLocaleString('en-PH', {minimumFractionDigits: 2})}</strong> at the gym reception before your session.</p>
                    <p>📍 <strong>Location:</strong> City Gymnasium Reception Desk</p>
                    <p>⏰ <strong>Payment due:</strong> Before your scheduled session</p>
                    <p class="pt-2 border-t border-yellow-300 mt-2">
                        <i class="fas fa-info-circle mr-1"></i>
                        <strong>Note:</strong> Please arrive 15 minutes early to complete payment.
                    </p>
                </div>
            </div>
        `;
    } else if (paymentMethod === 'gcash') {
        instructionsHTML = `
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <h4 class="font-bold text-blue-800 mb-3 flex items-center">
                    <i class="fas fa-mobile-alt text-blue-600 mr-2"></i>GCash Payment Instructions
                </h4>
                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <div class="bg-white p-4 rounded-lg border border-blue-200 text-center">
                            <div class="bg-gray-100 p-4 rounded-lg mb-3">
                                <i class="fas fa-qrcode text-6xl text-blue-600"></i>
                            </div>
                            <p class="text-sm text-gray-600 mb-2">Scan this QR code in your GCash app</p>
                            <p class="text-xs text-gray-500">(QR Code placeholder - Add actual QR in production)</p>
                        </div>
                    </div>
                    <div class="space-y-3 text-sm">
                        <div>
                            <p class="font-semibold text-gray-700">Amount to Pay:</p>
                            <p class="text-xl font-bold text-blue-600">₱${parseFloat(currentBookingData.total_amount).toLocaleString('en-PH', {minimumFractionDigits: 2})}</p>
                        </div>
                        <div>
                            <p class="font-semibold text-gray-700">GCash Number:</p>
                            <p class="font-mono text-lg">0917-123-4567</p>
                        </div>
                        <div>
                            <p class="font-semibold text-gray-700">Account Name:</p>
                            <p>City Gymnasium</p>
                        </div>
                        <div class="pt-2 border-t">
                            <p class="text-xs text-gray-600">
                                <i class="fas fa-exclamation-circle mr-1"></i>
                                After payment, please take a screenshot of your receipt and upload it in the Payments section.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        `;
    } else if (paymentMethod === 'maya') {
        instructionsHTML = `
            <div class="bg-purple-50 border border-purple-200 rounded-lg p-4">
                <h4 class="font-bold text-purple-800 mb-3 flex items-center">
                    <i class="fas fa-credit-card text-purple-600 mr-2"></i>Maya Payment Instructions
                </h4>
                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <div class="bg-white p-4 rounded-lg border border-purple-200 text-center">
                            <div class="bg-gray-100 p-4 rounded-lg mb-3">
                                <i class="fas fa-qrcode text-6xl text-purple-600"></i>
                            </div>
                            <p class="text-sm text-gray-600 mb-2">Scan this QR code in your Maya app</p>
                            <p class="text-xs text-gray-500">(QR Code placeholder - Add actual QR in production)</p>
                        </div>
                    </div>
                    <div class="space-y-3 text-sm">
                        <div>
                            <p class="font-semibold text-gray-700">Amount to Pay:</p>
                            <p class="text-xl font-bold text-purple-600">₱${parseFloat(currentBookingData.total_amount).toLocaleString('en-PH', {minimumFractionDigits: 2})}</p>
                        </div>
                        <div>
                            <p class="font-semibold text-gray-700">Maya Number:</p>
                            <p class="font-mono text-lg">0917-123-4567</p>
                        </div>
                        <div>
                            <p class="font-semibold text-gray-700">Account Name:</p>
                            <p>City Gymnasium</p>
                        </div>
                        <div class="pt-2 border-t">
                            <p class="text-xs text-gray-600">
                                <i class="fas fa-exclamation-circle mr-1"></i>
                                After payment, please take a screenshot of your receipt and upload it in the Payments section.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        `;
    } else if (paymentMethod === 'bank_transfer') {
        instructionsHTML = `
            <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                <h4 class="font-bold text-green-800 mb-3 flex items-center">
                    <i class="fas fa-university text-green-600 mr-2"></i>Bank Transfer Instructions
                </h4>
                <div class="space-y-3 text-sm">
                    <div class="bg-white p-4 rounded-lg border border-green-200">
                        <div class="grid md:grid-cols-2 gap-4">
                            <div>
                                <p class="font-semibold text-gray-700 mb-1">Bank Name:</p>
                                <p class="text-lg">BDO Unibank</p>
                            </div>
                            <div>
                                <p class="font-semibold text-gray-700 mb-1">Account Number:</p>
                                <p class="font-mono text-lg">0123-4567-8901</p>
                            </div>
                            <div>
                                <p class="font-semibold text-gray-700 mb-1">Account Name:</p>
                                <p>City Gymnasium Corp.</p>
                            </div>
                            <div>
                                <p class="font-semibold text-gray-700 mb-1">Amount to Transfer:</p>
                                <p class="text-xl font-bold text-green-600">₱${parseFloat(currentBookingData.total_amount).toLocaleString('en-PH', {minimumFractionDigits: 2})}</p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-3">
                        <p class="font-semibold text-yellow-800 mb-2">
                            <i class="fas fa-exclamation-triangle mr-1"></i>Important Instructions:
                        </p>
                        <ol class="list-decimal list-inside space-y-1 text-yellow-900">
                            <li>Transfer the exact amount shown above</li>
                            <li>Use your booking number <strong>${currentBookingData.booking_number}</strong> as reference</li>
                            <li>Take a photo of your transfer receipt</li>
                            <li>Upload the receipt in the Payments section</li>
                            <li>Wait for admin verification (usually within 24 hours)</li>
                        </ol>
                    </div>
                </div>
            </div>
        `;
    }
    
    document.getElementById('paymentInstructions').innerHTML = instructionsHTML;
}

function getPaymentMethodName(method) {
    const names = {
        'cash': '💵 Cash (Pay at Gym)',
        'gcash': '📱 GCash',
        'maya': '💳 Maya',
        'bank_transfer': '🏦 Bank Transfer'
    };
    return names[method] || method;
}

function goToPayments() {
    window.location.href = '/user/payments';
}

function closeBookingModal() {
    document.getElementById('bookingModal').classList.add('hidden');
    document.getElementById('bookingStep').classList.remove('hidden');
    document.getElementById('paymentStep').classList.add('hidden');
    
    document.getElementById('bookingForm').reset();
    selectedSchedule = null;
    currentBookingData = null;
}

function updateModalTotal() {
    if (!selectedSchedule) return;
    const total = selectedSchedule.total_price || (selectedSchedule.price_per_slot * selectedSchedule.duration_hours);
    document.getElementById('modal_total').textContent =
        '₱' + total.toLocaleString('en-PH', { minimumFractionDigits: 2 });
}


function bookSchedule(scheduleId) {
    fetch(`/user/schedule/${scheduleId}`)
        .then(response => response.json())
        .then(schedule => {
            selectedSchedule = schedule;
            
            document.getElementById('modal_schedule_id').value = schedule.id;
            document.getElementById('modalScheduleDetails').innerHTML = `
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-sm text-blue-600 font-semibold mb-1">${schedule.date}</p>
                        <p class="text-lg font-bold text-gray-800">
                            <i class="fas fa-clock mr-2"></i>${schedule.time_slot}
                        </p>
                        <p class="text-sm text-gray-600 mt-1">
                            Duration: <span class="font-semibold">${schedule.duration_hours} hour(s)</span>
                        </p>
                    </div>
                    <div class="text-right">
                        <p class="text-sm text-gray-600">Rate</p>
                        <p class="text-md font-semibold text-blue-600">
                            ₱${schedule.price_per_slot.toLocaleString('en-PH')}/hour
                        </p>
                        <p class="text-xs text-gray-500 mt-1">Total for this slot:</p>
                        <p class="text-xl font-bold text-green-600">
                            ₱${schedule.total_price.toLocaleString('en-PH')}
                        </p>
                    </div>
                </div>
            `;

            
            updateModalTotal();
            document.getElementById('bookingModal').classList.remove('hidden');
        })
        .catch(error => {
            console.error('Error loading schedule:', error);
            alert('Error loading schedule details. Please try again.');
        });
}
</script>
@endsection

@push('scripts')
<script>
let currentMonth = {{ $month }};
let currentYear  = {{ $year }};
let schedulesData = @json($schedules);
let selectedSchedule = null;

document.addEventListener('DOMContentLoaded', function() {
    renderCalendar();
    
    document.getElementById('prevMonth').addEventListener('click', () => {
        currentMonth--;
        if (currentMonth < 1) {
            currentMonth = 12;
            currentYear--;
        }
        loadSchedules();
    });
    
    document.getElementById('nextMonth').addEventListener('click', () => {
        currentMonth++;
        if (currentMonth > 12) {
            currentMonth = 1;
            currentYear++;
        }
        loadSchedules();
    });
});

function renderCalendar() {
    const monthNames = [
        "January", "February", "March", "April", "May", "June",
        "July", "August", "September", "October", "November", "December"
    ];

    document.getElementById('monthYear').textContent =
        `${monthNames[currentMonth - 1]} ${currentYear}`;

    const firstDay    = new Date(currentYear, currentMonth - 1, 1).getDay();
    const daysInMonth = new Date(currentYear, currentMonth, 0).getDate();

    const today = new Date();
    today.setHours(0, 0, 0, 0);

    let calendarHTML = '';

    // leading blanks
    for (let i = 0; i < firstDay; i++) {
        calendarHTML += '<div class="p-2"></div>';
    }

    for (let day = 1; day <= daysInMonth; day++) {
        const dateStr = `${currentYear}-${String(currentMonth).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
        const dateObj = new Date(currentYear, currentMonth - 1, day, 12, 0, 0);
        const isPast  = dateObj < today;

        const daySchedules = schedulesData.filter(s => s.date === dateStr);

        let bgColor  = 'bg-gray-100';
        let textColor = 'text-gray-400';
        let cursor   = 'cursor-not-allowed';

        if (!isPast && daySchedules.length > 0) {

            // ✅ “Bookable” means: user can still book at least one schedule
            const hasBookableSlot = daySchedules.some(s =>
                s.status === 'available' &&
                !s.is_full &&
                s.available_slots > 0 &&
                !s.user_has_booking   // 👈 user has not already booked this schedule
            );

            if (hasBookableSlot) {
                // 🟢 at least one slot the user can still book
                bgColor  = 'bg-green-100 hover:bg-green-200';
                textColor = 'text-green-800';
                cursor   = 'cursor-pointer';
            } else {
                // 🔴 there are schedules, but none bookable for this user
                bgColor  = 'bg-red-100';
                textColor = 'text-red-800';
                cursor   = 'cursor-not-allowed'; // or pointer if you still want click
            }
        }

        calendarHTML += `
            <div class="p-2 text-center rounded-lg ${bgColor} ${textColor} ${cursor} transition"
                 onclick="${!isPast && daySchedules.length > 0 ? `viewDaySchedules('${dateStr}')` : ''}">
                <div class="font-semibold">${day}</div>
            </div>
        `;
    }

    document.getElementById('calendar').innerHTML = calendarHTML;
}



function viewDaySchedules(dateStr) {
    const date = new Date(dateStr + 'T12:00:00');
    const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
    document.getElementById('selectedDateDisplay').textContent =
        date.toLocaleDateString('en-US', options);
    
    fetch(`/user/schedule/date?date=${dateStr}`)
        .then(response => response.json())
        .then(schedules => {
            let html = '';
            
            if (schedules.length === 0) {
                html = '<div class="text-center text-gray-500 py-4">No slots available</div>';
            } else {
                schedules.forEach(schedule => {
                    const userBooked = !!schedule.user_has_booking;
                    const isAvailable = schedule.status === 'available' &&
                                        !schedule.is_full &&
                                        !userBooked;

                    html += `
    <div class="border border-gray-200 rounded-lg p-4
                ${isAvailable ? 'hover:border-blue-500 cursor-pointer' : 'opacity-60 cursor-not-allowed'}"
         ${isAvailable ? `onclick="bookSchedule(${schedule.id})"` : ''}>
         
        <div class="flex items-center justify-between mb-2">
            <span class="font-semibold text-gray-800">
                <i class="fas fa-clock text-blue-500 mr-2"></i>${schedule.time_slot}
            </span>
            <span class="px-2 py-1 text-xs rounded-full
                        ${
                            userBooked
                            ? 'bg-gray-200 text-gray-700'
                            : (isAvailable ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800')
                        }">
                ${
                    userBooked
                    ? 'Booked'
                    : (isAvailable ? 'Available' : 'Full')
                }
            </span>
        </div>

        <div class="flex items-center justify-between text-sm">
            <span class="text-gray-600">
                <i class="fas fa-users mr-1"></i>Capacity: ${schedule.total_capacity}
            </span>
            <span class="text-blue-600 font-bold">
                ₱${schedule.total_price.toLocaleString('en-PH')} total
                <span class="text-xs text-gray-500">(${schedule.duration_hours} hr)</span>
            </span>
        </div>

        ${
            schedule.notes
            ? `
                <div class="mt-3 p-2 bg-yellow-50 border border-yellow-200 rounded text-xs text-yellow-800">
                    <i class="fas fa-sticky-note mr-1"></i>
                    <strong>Note:</strong> ${schedule.notes}
                </div>
              `
            : ''
        }

        ${
            userBooked
            ? '<p class="mt-2 text-xs text-gray-500">You already booked this slot.</p>'
            : ''
        }
    </div>
`;

                });
            }
            
            document.getElementById('timeSlotsContainer').innerHTML = html;
        })
        .catch(error => {
            console.error('Error loading schedules:', error);
            document.getElementById('timeSlotsContainer').innerHTML = 
                '<div class="text-center text-red-500 py-4">Error loading schedules</div>';
        });
}

function loadSchedules() {
    window.location.href = `/user/schedule?month=${currentMonth}&year=${currentYear}`;
}
</script>
@endpush
