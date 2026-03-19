<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Booking;
use App\Models\Refund;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PaymentController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $payments = Payment::with('booking.schedule')
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        $pendingPayments = Payment::with('booking.schedule')
            ->where('user_id', $user->id)
            ->where('status', 'pending')
            ->get()
            ->map(function ($payment) {
                return [
                    'id' => $payment->id,
                    'payment_number' => $payment->payment_number,
                    'amount' => (float) $payment->amount,
                    'booking' => [
                        'booking_number' => $payment->booking?->booking_number,
                        'schedule_date' => optional($payment->booking?->schedule?->date)->format('M d, Y'),
                        'time_slot' => $payment->booking?->schedule?->timeSlot
                            ?? $payment->booking?->schedule?->time_slot
                            ?? null,
                    ],
                ];
            })
            ->values();

        $totalPaid = Payment::where('user_id', $user->id)
            ->where('status', 'completed')
            ->sum('amount');

        $totalPending = Payment::where('user_id', $user->id)
            ->where('status', 'pending')
            ->sum('amount');

        $paymentItems = collect($payments->items())->map(function ($payment) {
            return [
                'id' => $payment->id,
                'payment_number' => $payment->payment_number,
                'transaction_id' => $payment->transaction_id,
                'payment_method' => $payment->payment_method,
                'amount' => (float) $payment->amount,
                'status' => $payment->status,
                'status_label' => ucfirst($payment->status),
                'display_date' => $payment->paid_at
                    ? $payment->paid_at->format('M d, Y')
                    : $payment->created_at->format('M d, Y'),
                'can_refund' => (bool) ($payment->booking?->canRefund ?? false),
                'booking' => [
                    'booking_number' => $payment->booking?->booking_number,
                    'schedule_date' => optional($payment->booking?->schedule?->date)->format('M d, Y'),
                    'time_slot' => $payment->booking?->schedule?->timeSlot
                        ?? $payment->booking?->schedule?->time_slot
                        ?? null,
                ],
            ];
        })->values();

        $unreadNotifications = Notification::where('user_id', $user->id)
            ->where('is_read', false)
            ->count();

        return response()->json([
            'user' => [
                'name' => $user->name,
                'role' => 'Member',
            ],
            'unreadNotifications' => $unreadNotifications,
            'stats' => [
                'totalPaid' => (float) $totalPaid,
                'totalPending' => (float) $totalPending,
                'transactions' => $payments->total(),
            ],
            'pendingPayments' => $pendingPayments,
            'payments' => [
                'data' => $paymentItems,
                'current_page' => $payments->currentPage(),
                'last_page' => $payments->lastPage(),
                'per_page' => $payments->perPage(),
                'total' => $payments->total(),
                'from' => $payments->firstItem(),
                'to' => $payments->lastItem(),
                'prev_page_url' => $payments->previousPageUrl(),
                'next_page_url' => $payments->nextPageUrl(),
            ],
        ]);
    }

    public function processPayment(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'payment_method' => 'required|in:cash,gcash,paymaya,credit_card,debit_card,bank_transfer',
            'reference_number' => 'required_if:payment_method,gcash,paymaya,bank_transfer|nullable|string',
            'receipt' => 'nullable|image|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $payment = Payment::where('id', $id)
            ->where('user_id', Auth::id())
            ->where('status', 'pending')
            ->firstOrFail();

        DB::beginTransaction();

        try {
            $receiptPath = null;

            if ($request->hasFile('receipt')) {
                $receiptPath = $request->file('receipt')->store('receipts', 'public');
            }

            $payment->update([
                'payment_method' => $request->payment_method,
                'status' => 'completed',
                'transaction_id' => $request->reference_number ?? 'CASH-' . time(),
                'payment_details' => json_encode([
                    'reference_number' => $request->reference_number,
                    'receipt_path' => $receiptPath,
                ]),
                'paid_at' => now(),
            ]);

            $payment->booking->update(['status' => 'confirmed']);

            Notification::create([
                'user_id' => Auth::id(),
                'type' => 'payment_received',
                'title' => 'Payment Confirmed',
                'message' => "Your payment of ₱" . number_format($payment->amount, 2) . " has been received. Booking confirmed!",
                'data' => json_encode(['payment_id' => $payment->id]),
            ]);

            if (function_exists('activity')) {
                activity()
                    ->causedBy(Auth::user())
                    ->performedOn($payment)
                    ->log('Completed payment: ' . $payment->payment_number);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Payment processed successfully!',
                'payment' => [
                    'payment_number' => $payment->payment_number,
                    'amount' => $payment->amount,
                    'booking_number' => $payment->booking->booking_number,
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while processing your payment.'
            ], 500);
        }
    }

    public function requestRefund(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'reason' => 'required|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $payment = Payment::where('id', $id)
            ->where('user_id', Auth::id())
            ->where('status', 'completed')
            ->firstOrFail();

        $booking = $payment->booking;

        if (! $booking->canRefund) {
            return response()->json([
                'success' => false,
                'message' => 'This booking is not eligible for a refund.'
            ], 400);
        }

        if (Refund::where('booking_id', $booking->id)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'A refund request already exists for this booking.'
            ], 400);
        }

        DB::beginTransaction();

        try {
            $refund = Refund::create([
                'booking_id' => $booking->id,
                'payment_id' => $payment->id,
                'user_id' => Auth::id(),
                'refund_amount' => $payment->amount,
                'original_amount' => $payment->amount,
                'reason' => $request->reason,
                'status' => 'pending',
            ]);

            Notification::create([
                'user_id' => Auth::id(),
                'type' => 'refund_approved',
                'title' => 'Refund Request Submitted',
                'message' => "Your refund request for booking {$booking->booking_number} has been submitted and is pending review.",
                'data' => json_encode(['refund_id' => $refund->id]),
            ]);

            if (function_exists('activity')) {
                activity()
                    ->causedBy(Auth::user())
                    ->performedOn($refund)
                    ->log('Requested refund: ' . $refund->refund_number);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Refund request submitted successfully! We will review it within 1-3 business days.',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while submitting your refund request.'
            ], 500);
        }
    }
}