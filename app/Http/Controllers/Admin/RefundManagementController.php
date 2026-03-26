<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Refund;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class RefundManagementController extends Controller
{
    public function index(Request $request)
    {
        $query = Refund::with(['booking.user', 'booking.schedule', 'payment', 'user', 'processedBy']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('refund_number', 'like', "%{$search}%")
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

        if ($request->filled('date_from')) {
            $query->whereDate('requested_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('requested_at', '<=', $request->date_to);
        }

        $refunds = $query
            ->latest('requested_at')
            ->get()
            ->map(function ($refund) {
                return [
                    'id' => $refund->id,
                    'refund_number' => $refund->refund_number,
                    'original_amount' => (float) $refund->original_amount,
                    'refund_amount' => (float) $refund->refund_amount,
                    'reason' => $refund->reason,
                    'status' => $refund->status,
                    'admin_notes' => $refund->admin_notes,
                    'requested_at' => $refund->requested_at ? Carbon::parse($refund->requested_at)->format('M d, Y h:i A') : null,
                    'requested_at_raw' => $refund->requested_at ? Carbon::parse($refund->requested_at)->format('Y-m-d') : null,
                    'processed_at' => $refund->processed_at ? Carbon::parse($refund->processed_at)->format('M d, Y h:i A') : null,
                    'user' => $refund->user ? [
                        'id' => $refund->user->id,
                        'name' => $refund->user->name,
                        'email' => $refund->user->email,
                    ] : null,
                    'booking' => $refund->booking ? [
                        'id' => $refund->booking->id,
                        'booking_number' => $refund->booking->booking_number,
                        'schedule' => $refund->booking->schedule ? [
                            'id' => $refund->booking->schedule->id,
                            'date' => $refund->booking->schedule->date,
                            'date_formatted' => $refund->booking->schedule->date
                                ? Carbon::parse($refund->booking->schedule->date)->format('M d, Y')
                                : null,
                        ] : null,
                    ] : null,
                    'payment' => $refund->payment ? [
                        'id' => $refund->payment->id,
                        'payment_number' => $refund->payment->payment_number,
                    ] : null,
                    'processed_by' => $refund->processedBy ? [
                        'id' => $refund->processedBy->id,
                        'name' => $refund->processedBy->name,
                        'email' => $refund->processedBy->email,
                    ] : null,
                ];
            })
            ->values();

        return response()->json([
            'refunds' => $refunds,
        ]);
    }

    public function stats()
    {
        return response()->json([
            'pendingRefunds' => Refund::where('status', 'pending')->count(),
            'pendingAmount' => Refund::where('status', 'pending')->sum('refund_amount'),
            'approvedRefunds' => Refund::where('status', 'approved')->count(),
            'completedRefunds' => Refund::where('status', 'completed')->count(),
            'totalRefundAmount' => Refund::whereIn('status', ['approved', 'completed'])->sum('refund_amount'),
        ]);
    }

    public function show($id)
    {
        $refund = Refund::with(['booking.user', 'booking.schedule', 'payment', 'user', 'processedBy'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'refund' => $refund,
        ]);
    }

    public function approve(Request $request, $id)
    {
        $refund = Refund::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'admin_notes' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        if ($refund->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Only pending refunds can be approved.',
            ], 422);
        }

        try {
            DB::beginTransaction();

            if (method_exists($refund, 'approve')) {
                $refund->approve(auth()->id(), $request->admin_notes);
            } else {
                $refund->status = 'approved';
                $refund->processed_by = auth()->id();
                $refund->processed_at = now();
                $refund->admin_notes = $request->admin_notes;
                $refund->save();
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Refund approved successfully!',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to approve refund: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function reject(Request $request, $id)
    {
        $refund = Refund::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'admin_notes' => 'required|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        if ($refund->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Only pending refunds can be rejected.',
            ], 422);
        }

        try {
            DB::beginTransaction();

            if (method_exists($refund, 'reject')) {
                $refund->reject(auth()->id(), $request->admin_notes);
            } else {
                $refund->status = 'rejected';
                $refund->processed_by = auth()->id();
                $refund->processed_at = now();
                $refund->admin_notes = $request->admin_notes;
                $refund->save();
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Refund rejected successfully!',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to reject refund: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function complete(Request $request, $id)
    {
        $refund = Refund::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'transaction_reference' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        if ($refund->status !== 'approved') {
            return response()->json([
                'success' => false,
                'message' => 'Only approved refunds can be marked as completed.',
            ], 422);
        }

        try {
            DB::beginTransaction();

            if (method_exists($refund, 'markAsCompleted')) {
                $refund->markAsCompleted();
            } else {
                $refund->status = 'completed';
                $refund->processed_at = now();
                $refund->save();
            }

            if ($request->filled('transaction_reference')) {
                $refund->update([
                    'admin_notes' => trim(($refund->admin_notes ?? '') . ' | Transaction Ref: ' . $request->transaction_reference),
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Refund marked as completed successfully!',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to complete refund: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $refund = Refund::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'refund_amount' => 'required|numeric|min:0|max:' . $refund->original_amount,
            'admin_notes' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        if (!in_array($refund->status, ['pending', 'approved'])) {
            return response()->json([
                'success' => false,
                'message' => 'Only pending or approved refunds can be updated.',
            ], 422);
        }

        try {
            DB::beginTransaction();

            $refund->update([
                'refund_amount' => $request->refund_amount,
                'admin_notes' => $request->admin_notes,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Refund updated successfully!',
                'refund' => $refund->load(['user', 'booking']),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to update refund: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function destroy($id)
    {
        $refund = Refund::findOrFail($id);

        if (in_array($refund->status, ['completed'])) {
            return response()->json([
                'success' => false,
                'message' => 'Completed refunds cannot be deleted.',
            ], 422);
        }

        try {
            DB::beginTransaction();

            $refund->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Refund deleted successfully!',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete refund: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function exportReport(Request $request)
    {
        $query = Refund::with(['booking.user', 'user', 'processedBy']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('refund_number', 'like', "%{$search}%")
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

        if ($request->filled('date_from')) {
            $query->whereDate('requested_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('requested_at', '<=', $request->date_to);
        }

        $refunds = $query->get();

        $filename = 'refunds_report_' . now()->format('Y-m-d_His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function () use ($refunds) {
            $file = fopen('php://output', 'w');

            fputcsv($file, [
                'Refund Number',
                'Booking Number',
                'Customer Name',
                'Original Amount',
                'Refund Amount',
                'Reason',
                'Status',
                'Requested Date',
                'Processed Date',
                'Processed By',
            ]);

            foreach ($refunds as $refund) {
                fputcsv($file, [
                    $refund->refund_number,
                    $refund->booking?->booking_number,
                    $refund->user?->name,
                    $refund->original_amount,
                    $refund->refund_amount,
                    $refund->reason,
                    ucfirst($refund->status),
                    $refund->requested_at ? Carbon::parse($refund->requested_at)->format('Y-m-d H:i:s') : 'N/A',
                    $refund->processed_at ? Carbon::parse($refund->processed_at)->format('Y-m-d H:i:s') : 'N/A',
                    $refund->processedBy?->name ?? 'N/A',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}