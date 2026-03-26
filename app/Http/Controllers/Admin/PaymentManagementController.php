<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class PaymentManagementController extends Controller
{
    public function index(Request $request)
    {
        $query = Payment::with(['booking.user', 'booking.schedule', 'user']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('payment_number', 'like', "%{$search}%")
                    ->orWhere('transaction_id', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    })
                    ->orWhereHas('booking', function ($bookingQuery) use ($search) {
                        $bookingQuery->where('booking_number', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $payments = $query
            ->latest()
            ->get()
            ->map(function ($payment) {
                return [
                    'id' => $payment->id,
                    'payment_number' => $payment->payment_number,
                    'amount' => (float) $payment->amount,
                    'payment_method' => $payment->payment_method,
                    'transaction_id' => $payment->transaction_id,
                    'payment_details' => $payment->payment_details,
                    'status' => $payment->status,
                    'paid_at' => $payment->paid_at ? Carbon::parse($payment->paid_at)->format('M d, Y h:i A') : null,
                    'created_at' => $payment->created_at ? $payment->created_at->format('M d, Y h:i A') : null,
                    'created_at_raw' => $payment->created_at ? $payment->created_at->format('Y-m-d') : null,
                    'created_at_date' => $payment->created_at ? $payment->created_at->format('Y-m-d') : null,
                    'created_at_formatted' => $payment->created_at ? $payment->created_at->format('M d, Y h:i A') : null,
                    'user' => $payment->user ? [
                        'id' => $payment->user->id,
                        'name' => $payment->user->name,
                        'email' => $payment->user->email,
                    ] : null,
                    'booking' => $payment->booking ? [
                        'id' => $payment->booking->id,
                        'booking_number' => $payment->booking->booking_number,
                        'schedule' => $payment->booking->schedule ? [
                            'id' => $payment->booking->schedule->id,
                            'date' => $payment->booking->schedule->date,
                            'date_formatted' => $payment->booking->schedule->date
                                ? Carbon::parse($payment->booking->schedule->date)->format('M d, Y')
                                : null,
                        ] : null,
                    ] : null,
                ];
            })
            ->values();

        return response()->json([
            'payments' => $payments,
        ]);
    }

    public function stats()
    {
        return response()->json([
            'totalRevenue' => Payment::where('status', 'completed')->sum('amount'),
            'completedPayments' => Payment::where('status', 'completed')->count(),
            'pendingPayments' => Payment::where('status', 'pending')->count(),
            'todayRevenue' => Payment::where('status', 'completed')
                ->whereDate('created_at', Carbon::today())
                ->sum('amount'),
        ]);
    }

    public function methodStats()
    {
        $paymentMethodStats = Payment::where('status', 'completed')
            ->select('payment_method', DB::raw('count(*) as count'), DB::raw('sum(amount) as total'))
            ->groupBy('payment_method')
            ->get();

        return response()->json([
            'paymentMethodStats' => $paymentMethodStats,
        ]);
    }

    public function show($id)
    {
        $payment = Payment::with(['booking.user', 'booking.schedule', 'user', 'refund'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'payment' => $payment,
        ]);
    }

    public function verify(Request $request, $id)
    {
        $payment = Payment::with('booking')->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'transaction_id' => 'required|string|max:255',
            'payment_details' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        if ($payment->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Only pending payments can be verified.',
            ], 422);
        }

        try {
            DB::beginTransaction();

            if (method_exists($payment, 'markAsCompleted')) {
                $payment->markAsCompleted($request->transaction_id);
            } else {
                $payment->status = 'completed';
                $payment->transaction_id = $request->transaction_id;
                $payment->paid_at = now();
                $payment->save();
            }

            if ($request->filled('payment_details')) {
                $payment->update([
                    'payment_details' => $request->payment_details,
                ]);
            }

            if ($payment->booking && $payment->booking->status === 'pending') {
                if (method_exists($payment->booking, 'confirm')) {
                    $payment->booking->confirm();
                } else {
                    $payment->booking->status = 'confirmed';
                    $payment->booking->save();
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Payment verified successfully!',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to verify payment: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function markAsFailed(Request $request, $id)
    {
        $payment = Payment::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'reason' => 'required|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        if ($payment->status === 'completed') {
            return response()->json([
                'success' => false,
                'message' => 'Completed payments cannot be marked as failed.',
            ], 422);
        }

        try {
            DB::beginTransaction();

            if (method_exists($payment, 'markAsFailed')) {
                $payment->markAsFailed();
            } else {
                $payment->status = 'failed';
                $payment->save();
            }

            $payment->update([
                'payment_details' => 'Failed: ' . $request->reason,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Payment marked as failed!',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to update payment: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $payment = Payment::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'payment_method' => 'required|in:cash,gcash,maya,credit_card,debit_card,bank_transfer',
            'transaction_id' => 'nullable|string|max:255',
            'payment_details' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            DB::beginTransaction();

            $payment->update([
                'payment_method' => $request->payment_method,
                'transaction_id' => $request->transaction_id,
                'payment_details' => $request->payment_details,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Payment updated successfully!',
                'payment' => $payment->load(['booking.user']),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to update payment: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function destroy($id)
    {
        $payment = Payment::findOrFail($id);

        if ($payment->status === 'completed') {
            return response()->json([
                'success' => false,
                'message' => 'Completed payments cannot be deleted.',
            ], 422);
        }

        try {
            DB::beginTransaction();

            $payment->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Payment deleted successfully!',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete payment: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function exportReport(Request $request)
    {
        $query = Payment::with(['booking.user', 'user']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('payment_number', 'like', "%{$search}%")
                    ->orWhere('transaction_id', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    })
                    ->orWhereHas('booking', function ($bookingQuery) use ($search) {
                        $bookingQuery->where('booking_number', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $payments = $query->get();

        $filename = 'payments_report_' . now()->format('Y-m-d_His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function () use ($payments) {
            $file = fopen('php://output', 'w');

            fputcsv($file, [
                'Payment Number',
                'Booking Number',
                'Customer Name',
                'Amount',
                'Payment Method',
                'Transaction ID',
                'Status',
                'Payment Date',
            ]);

            foreach ($payments as $payment) {
                fputcsv($file, [
                    $payment->payment_number,
                    $payment->booking?->booking_number,
                    $payment->user?->name,
                    $payment->amount,
                    ucfirst(str_replace('_', ' ', $payment->payment_method)),
                    $payment->transaction_id ?? 'N/A',
                    ucfirst($payment->status),
                    $payment->created_at?->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}