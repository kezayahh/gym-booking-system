<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class PaymentManagementController extends Controller
{
    public function index(Request $request)
    {
        $query = Payment::with(['booking.user', 'booking.schedule', 'user']);

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('payment_number', 'like', "%{$search}%")
                  ->orWhere('transaction_id', 'like', "%{$search}%")
                  ->orWhereHas('user', function($userQuery) use ($search) {
                      $userQuery->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                  })
                  ->orWhereHas('booking', function($bookingQuery) use ($search) {
                      $bookingQuery->where('booking_number', 'like', "%{$search}%");
                  });
            });
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by payment method
        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $payments = $query->latest()->paginate(10);
        
        // Statistics
        $totalPayments = Payment::count();
        $completedPayments = Payment::where('status', 'completed')->count();
        $pendingPayments = Payment::where('status', 'pending')->count();
        $failedPayments = Payment::where('status', 'failed')->count();
        
        $totalRevenue = Payment::where('status', 'completed')->sum('amount');
        $todayRevenue = Payment::where('status', 'completed')
            ->whereDate('created_at', Carbon::today())
            ->sum('amount');
        
        $monthlyRevenue = Payment::where('status', 'completed')
            ->whereMonth('created_at', Carbon::now()->month)
            ->whereYear('created_at', Carbon::now()->year)
            ->sum('amount');

        // Payment method breakdown
        $paymentMethodStats = Payment::where('status', 'completed')
            ->select('payment_method', DB::raw('count(*) as count'), DB::raw('sum(amount) as total'))
            ->groupBy('payment_method')
            ->get();

        return view('admin.payment-management', compact(
            'payments', 'totalPayments', 'completedPayments', 'pendingPayments',
            'failedPayments', 'totalRevenue', 'todayRevenue', 'monthlyRevenue',
            'paymentMethodStats'
        ));
    }

    public function show($id)
    {
        $payment = Payment::with(['booking.user', 'booking.schedule', 'user', 'refund'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'payment' => $payment
        ]);
    }

    public function verify(Request $request, $id)
    {
        $payment = Payment::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'transaction_id' => 'required|string|max:255',
            'payment_details' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        if ($payment->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Only pending payments can be verified.'
            ], 422);
        }

        try {
            DB::beginTransaction();

            $payment->markAsCompleted($request->transaction_id);
            
            if ($request->filled('payment_details')) {
                $payment->update(['payment_details' => $request->payment_details]);
            }

            // Confirm the booking if it's still pending
            if ($payment->booking->status === 'pending') {
                $payment->booking->confirm();
            }

            // Log activity
            activity()
                ->causedBy(auth()->user())
                ->performedOn($payment)
                ->withProperties([
                    'payment_number' => $payment->payment_number,
                    'transaction_id' => $request->transaction_id,
                    'amount' => $payment->amount
                ])
                ->log('Admin verified payment: ' . $payment->payment_number);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Payment verified successfully!'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to verify payment: ' . $e->getMessage()
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
                'errors' => $validator->errors()
            ], 422);
        }

        if ($payment->status === 'completed') {
            return response()->json([
                'success' => false,
                'message' => 'Completed payments cannot be marked as failed.'
            ], 422);
        }

        try {
            DB::beginTransaction();

            $payment->markAsFailed();
            $payment->update(['payment_details' => 'Failed: ' . $request->reason]);

            // Log activity
            activity()
                ->causedBy(auth()->user())
                ->performedOn($payment)
                ->withProperties([
                    'payment_number' => $payment->payment_number,
                    'reason' => $request->reason
                ])
                ->log('Admin marked payment as failed: ' . $payment->payment_number);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Payment marked as failed!'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update payment: ' . $e->getMessage()
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
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $oldData = $payment->toArray();

            $payment->update([
                'payment_method' => $request->payment_method,
                'transaction_id' => $request->transaction_id,
                'payment_details' => $request->payment_details,
            ]);

            // Log activity
            activity()
                ->causedBy(auth()->user())
                ->performedOn($payment)
                ->withProperties([
                    'old' => $oldData,
                    'new' => $payment->toArray()
                ])
                ->log('Admin updated payment: ' . $payment->payment_number);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Payment updated successfully!',
                'payment' => $payment->load(['booking.user'])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update payment: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        $payment = Payment::findOrFail($id);

        // Prevent deletion of completed payments
        if ($payment->status === 'completed') {
            return response()->json([
                'success' => false,
                'message' => 'Completed payments cannot be deleted.'
            ], 422);
        }

        try {
            DB::beginTransaction();

            $paymentNumber = $payment->payment_number;

            // Log activity before deletion
            activity()
                ->causedBy(auth()->user())
                ->withProperties([
                    'payment_number' => $paymentNumber,
                    'booking_id' => $payment->booking_id
                ])
                ->log('Admin deleted payment: ' . $paymentNumber);

            $payment->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Payment deleted successfully!'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete payment: ' . $e->getMessage()
            ], 500);
        }
    }

    public function exportReport(Request $request)
    {
        $query = Payment::with(['booking.user', 'user']);

        // Apply filters
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

        // Generate CSV
        $filename = 'payments_report_' . now()->format('Y-m-d_His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($payments) {
            $file = fopen('php://output', 'w');
            
            // Header row
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

            // Data rows
            foreach ($payments as $payment) {
                fputcsv($file, [
                    $payment->payment_number,
                    $payment->booking->booking_number,
                    $payment->user->name,
                    $payment->amount,
                    ucfirst(str_replace('_', ' ', $payment->payment_method)),
                    $payment->transaction_id ?? 'N/A',
                    ucfirst($payment->status),
                    $payment->created_at->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}