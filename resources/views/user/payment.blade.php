{{-- File: resources/views/user/payment.blade.php --}}

@extends('layouts.user')

@section('title', 'Payments - City Gymnasium')

@section('content')
<div class="mb-6">
    <h1 class="text-3xl font-bold text-gray-800">
        <i class="fas fa-credit-card text-green-500 mr-2"></i>Payments
    </h1>
    <p class="text-gray-600 mt-1">Manage your payments and transaction history</p>
</div>

{{-- Payment Statistics --}}
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-lg shadow-lg p-6 text-white">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-green-100 text-sm font-medium mb-1">Total Paid</p>
                <h3 class="text-3xl font-bold">₱{{ number_format($totalPaid, 2) }}</h3>
                <p class="text-green-100 text-xs mt-2">
                    <i class="fas fa-check-circle mr-1"></i>All Time
                </p>
            </div>
            <div class="bg-white bg-opacity-20 rounded-full p-4">
                <i class="fas fa-check-circle text-3xl"></i>
            </div>
        </div>
    </div>

    <div class="bg-gradient-to-br from-yellow-500 to-yellow-600 rounded-lg shadow-lg p-6 text-white">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-yellow-100 text-sm font-medium mb-1">Pending</p>
                <h3 class="text-3xl font-bold">₱{{ number_format($totalPending, 2) }}</h3>
                <p class="text-yellow-100 text-xs mt-2">
                    <i class="fas fa-clock mr-1"></i>Awaiting Payment
                </p>
            </div>
            <div class="bg-white bg-opacity-20 rounded-full p-4">
                <i class="fas fa-hourglass-half text-3xl"></i>
            </div>
        </div>
    </div>

    <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-lg shadow-lg p-6 text-white">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-blue-100 text-sm font-medium mb-1">Transactions</p>
                <h3 class="text-3xl font-bold">{{ $payments->total() }}</h3>
                <p class="text-blue-100 text-xs mt-2">
                    <i class="fas fa-receipt mr-1"></i>Total Count
                </p>
            </div>
            <div class="bg-white bg-opacity-20 rounded-full p-4">
                <i class="fas fa-list text-3xl"></i>
            </div>
        </div>
    </div>
</div>

{{-- Pending Payments Section --}}
@if($pendingPayments->count() > 0)
<div class="bg-red-50 border-l-4 border-red-500 rounded-lg shadow-lg p-6 mb-8">
    <div class="flex items-start">
        <div class="flex-shrink-0">
            <i class="fas fa-exclamation-triangle text-red-500 text-2xl"></i>
        </div>
        <div class="ml-4 flex-1">
            <h3 class="text-lg font-bold text-red-800 mb-3">
                Pending Payments ({{ $pendingPayments->count() }})
            </h3>
            <p class="text-sm text-red-700 mb-4">Please complete these payments to confirm your bookings</p>
            
            <div class="space-y-3">
                @foreach($pendingPayments as $payment)
                <div class="bg-white rounded-lg p-4 border border-red-200">
                    <div class="flex items-center justify-between">
                        <div class="flex-1">
                            <p class="text-sm font-semibold text-gray-800">{{ $payment->payment_number }}</p>
                            <p class="text-xs text-gray-600 mt-1">
                                Booking: {{ $payment->booking->booking_number }} | 
                                {{ $payment->booking->schedule->date->format('M d, Y') }} | 
                                {{ $payment->booking->schedule->time_slot }}
                            </p>
                        </div>
                        <div class="text-right ml-4">
                            <p class="text-xl font-bold text-gray-800">₱{{ number_format($payment->amount, 2) }}</p>
                            <button onclick="openPaymentModal({{ $payment->id }}, '{{ $payment->payment_number }}', {{ $payment->amount }})"
                                    class="mt-2 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition text-sm font-semibold">
                                <i class="fas fa-credit-card mr-1"></i>Pay Now
                            </button>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endif

{{-- Payment History --}}
<div class="bg-white rounded-lg shadow-lg overflow-hidden">
    <div class="p-6 border-b border-gray-200 flex items-center justify-between">
        <h3 class="text-xl font-bold text-gray-800">
            <i class="fas fa-history mr-2"></i>Payment History
        </h3>
        <div class="flex items-center space-x-3">
            <select class="px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500">
                <option>All Status</option>
                <option>Completed</option>
                <option>Pending</option>
                <option>Failed</option>
                <option>Refunded</option>
            </select>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Payment #</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Booking Details</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Method</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($payments as $payment)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-medium text-gray-900">{{ $payment->payment_number }}</div>
                        @if($payment->transaction_id)
                        <div class="text-xs text-gray-500">Ref: {{ $payment->transaction_id }}</div>
                        @endif
                    </td>
                    <td class="px-6 py-4">
                        <div class="text-sm text-gray-900">{{ $payment->booking->booking_number }}</div>
                        <div class="text-xs text-gray-500">
                            <i class="fas fa-calendar mr-1"></i>{{ $payment->booking->schedule->date->format('M d, Y') }}
                        </div>
                        <div class="text-xs text-gray-500">
                            <i class="fas fa-clock mr-1"></i>{{ $payment->booking->schedule->time_slot }}
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="text-sm text-gray-900">
                            @if($payment->payment_method === 'gcash')
                                <i class="fas fa-mobile-alt text-blue-500 mr-1"></i>GCash
                            @elseif($payment->payment_method === 'cash')
                                <i class="fas fa-money-bill text-green-500 mr-1"></i>Cash
                            @elseif($payment->payment_method === 'credit_card')
                                <i class="fas fa-credit-card text-purple-500 mr-1"></i>Credit Card
                            @elseif($payment->payment_method === 'debit_card')
                                <i class="fas fa-credit-card text-blue-500 mr-1"></i>Debit Card
                            @else
                                <i class="fas fa-wallet text-gray-500 mr-1"></i>{{ ucfirst(str_replace('_', ' ', $payment->payment_method)) }}
                            @endif
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-bold text-gray-900">₱{{ number_format($payment->amount, 2) }}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        @if($payment->status === 'completed')
                            <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                <i class="fas fa-check-circle mr-1"></i>Paid
                            </span>
                        @elseif($payment->status === 'pending')
                            <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                <i class="fas fa-clock mr-1"></i>Pending
                            </span>
                        @elseif($payment->status === 'failed')
                            <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                <i class="fas fa-times-circle mr-1"></i>Failed
                            </span>
                        @elseif($payment->status === 'refunded')
                            <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                <i class="fas fa-undo mr-1"></i>Refunded
                            </span>
                        @else
                            <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                                {{ ucfirst($payment->status) }}
                            </span>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        {{ $payment->paid_at ? $payment->paid_at->format('M d, Y') : $payment->created_at->format('M d, Y') }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                        <div class="flex items-center space-x-2">
                            @if($payment->status === 'pending')
                                <button onclick="openPaymentModal({{ $payment->id }}, '{{ $payment->payment_number }}', {{ $payment->amount }})"
                                        class="text-green-600 hover:text-green-900 font-semibold">
                                    <i class="fas fa-credit-card mr-1"></i>Pay
                                </button>
                            @endif
                            
                            @if($payment->status === 'completed' && $payment->booking->canRefund)
                                <button onclick="openRefundModal({{ $payment->id }}, '{{ $payment->payment_number }}', {{ $payment->amount }})"
                                        class="text-orange-600 hover:text-orange-900 font-semibold">
                                    <i class="fas fa-undo mr-1"></i>Refund
                                </button>
                            @endif
                            
                            <button onclick="viewReceipt({{ $payment->id }})" class="text-blue-600 hover:text-blue-900">
                                <i class="fas fa-receipt"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-6 py-12 text-center">
                        <div class="text-gray-500">
                            <i class="fas fa-wallet text-6xl mb-4 text-gray-300"></i>
                            <p class="text-lg font-semibold mb-2">No Payment History</p>
                            <p class="text-sm">Make a booking to see your payments here</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($payments->hasPages())
    <div class="p-6 border-t border-gray-200">
        {{ $payments->links() }}
    </div>
    @endif
</div>

{{-- Payment Method Modal --}}
<div id="paymentModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-xl font-bold text-gray-800">Process Payment</h3>
            <button onclick="closePaymentModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        
        <div id="paymentDetails" class="mb-4 p-4 bg-blue-50 rounded-lg"></div>
        
        <form id="paymentForm" class="space-y-4" enctype="multipart/form-data">
            <input type="hidden" id="payment_id">
            
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                    Payment Method *
                </label>
                <select id="payment_method" name="payment_method" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent">
                    <option value="">Select Payment Method</option>
                    <option value="gcash">GCash</option>
                    <option value="paymaya">PayMaya</option>
                    <option value="cash">Cash</option>
                    <option value="credit_card">Credit Card</option>
                    <option value="debit_card">Debit Card</option>
                    <option value="bank_transfer">Bank Transfer</option>
                </select>
            </div>
            
            <div id="referenceField" class="hidden">
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                    Reference Number *
                </label>
                <input type="text" id="reference_number" name="reference_number"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent"
                       placeholder="Enter reference/transaction number">
            </div>
            
            <div id="receiptField" class="hidden">
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                    Upload Receipt (Optional)
                </label>
                <input type="file" id="receipt" name="receipt" accept="image/*"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent">
                <p class="text-xs text-gray-500 mt-1">Max size: 2MB | Formats: JPG, PNG</p>
            </div>
            
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-3">
                <p class="text-sm text-yellow-800">
                    <i class="fas fa-info-circle mr-1"></i>
                    For GCash/PayMaya: Send to <strong>09123456789</strong>
                </p>
            </div>
            
            <button type="submit" class="w-full px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition font-semibold">
                <i class="fas fa-check-circle mr-2"></i>Submit Payment
            </button>
        </form>
    </div>
</div>

{{-- Refund Request Modal --}}
<div id="refundModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-xl font-bold text-gray-800">Request Refund</h3>
            <button onclick="closeRefundModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        
        <div id="refundDetails" class="mb-4 p-4 bg-orange-50 rounded-lg"></div>
        
        <form id="refundForm" class="space-y-4">
            <input type="hidden" id="refund_payment_id">
            
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                    Reason for Refund *
                </label>
                <textarea id="refund_reason" name="reason" rows="4" required
                          class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent"
                          placeholder="Please explain why you're requesting a refund..."></textarea>
            </div>
            
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-3">
                <p class="text-sm text-blue-800">
                    <i class="fas fa-info-circle mr-1"></i>
                    Refunds are processed within 1-3 business days
                </p>
            </div>
            
            <button type="submit" class="w-full px-6 py-3 bg-orange-600 text-black rounded-lg hover:bg-orange-700 transition font-semibold">
                <i class="fas fa-paper-plane mr-2"></i>Submit Refund Request
            </button>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
// Payment Method Modal
function openPaymentModal(paymentId, paymentNumber, amount) {
    document.getElementById('payment_id').value = paymentId;
    document.getElementById('paymentDetails').innerHTML = `
        <p class="text-sm text-gray-600 mb-2">Payment Number</p>
        <p class="text-lg font-bold text-gray-800 mb-3">${paymentNumber}</p>
        <p class="text-2xl font-bold text-green-600">₱${parseFloat(amount).toFixed(2)}</p>
    `;
    document.getElementById('paymentModal').classList.remove('hidden');
}

function closePaymentModal() {
    document.getElementById('paymentModal').classList.add('hidden');
    document.getElementById('paymentForm').reset();
    document.getElementById('referenceField').classList.add('hidden');
    document.getElementById('receiptField').classList.add('hidden');
}

// Show/hide reference and receipt fields based on payment method
document.getElementById('payment_method').addEventListener('change', function() {
    const method = this.value;
    const referenceField = document.getElementById('referenceField');
    const receiptField = document.getElementById('receiptField');
    
    if (['gcash', 'paymaya', 'bank_transfer'].includes(method)) {
        referenceField.classList.remove('hidden');
        receiptField.classList.remove('hidden');
        document.getElementById('reference_number').required = true;
    } else {
        referenceField.classList.add('hidden');
        receiptField.classList.add('hidden');
        document.getElementById('reference_number').required = false;
    }
});

// Payment Form Submission
document.getElementById('paymentForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const paymentId = document.getElementById('payment_id').value;
    const formData = new FormData(e.target);
    
    fetch(`/user/payments/${paymentId}/process`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: formData
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            alert('Payment processed successfully!');
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

// Refund Modal Functions
function openRefundModal(paymentId, paymentNumber, amount) {
    document.getElementById('refund_payment_id').value = paymentId;
    document.getElementById('refundDetails').innerHTML = `
        <p class="text-sm text-gray-600 mb-2">Payment Number</p>
        <p class="text-lg font-bold text-gray-800 mb-3">${paymentNumber}</p>
        <p class="text-xl font-bold text-orange-600">Refund Amount: ₱${parseFloat(amount).toFixed(2)}</p>
    `;
    document.getElementById('refundModal').classList.remove('hidden');
}

function closeRefundModal() {
    document.getElementById('refundModal').classList.add('hidden');
    document.getElementById('refundForm').reset();
}

// Refund Form Submission
document.getElementById('refundForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const paymentId = document.getElementById('refund_payment_id').value;
    const reason = document.getElementById('refund_reason').value;
    
    if (!reason.trim()) {
        alert('Please provide a reason for the refund');
        return;
    }
    
    fetch(`/user/payments/${paymentId}/refund`, {
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
            alert(result.message);
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

function viewReceipt(paymentId) {
    // Implement receipt viewing
    alert('View receipt for payment ID: ' + paymentId);
}
</script>
@endpush