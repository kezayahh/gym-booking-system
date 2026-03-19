<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Refund;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class RefundManagementController extends Controller
{
    public function index(Request $request)
    {
        $query = Refund::with(['booking.user', 'booking.schedule', 'payment', 'user', 'processedBy']);

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('refund_number', 'like', "%{$search}%")
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

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('requested_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('requested_at', '<=', $request->date_to);
        }

        $refunds = $query->latest('requested_at')->paginate(10);
        
        // Statistics
        $totalRefunds = Refund::count();
        $pendingRefunds = Refund::where('status', 'pending')->count();
        $approvedRefunds = Refund::where('status', 'approved')->count();
        $rejectedRefunds = Refund::where('status', 'rejected')->count();
        $completedRefunds = Refund::where('status', 'completed')->count();
        
        $totalRefundAmount = Refund::whereIn('status', ['approved', 'completed'])
            ->sum('refund_amount');
        
        $pendingAmount = Refund::where('status', 'pending')->sum('refund_amount');

        return view('admin.refund-management', compact(
            'refunds', 'totalRefunds', 'pendingRefunds', 'approvedRefunds',
            'rejectedRefunds', 'completedRefunds', 'totalRefundAmount', 'pendingAmount'
        ));
    }

    public function show($id)
    {
        $refund = Refund::with(['booking.user', 'booking.schedule', 'payment', 'user', 'processedBy'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'refund' => $refund
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
                'errors' => $validator->errors()
            ], 422);
        }

        if ($refund->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Only pending refunds can be approved.'
            ], 422);
        }

        try {
            DB::beginTransaction();

            $refund->approve(auth()->id(), $request->admin_notes);

            // Log activity
            activity()
                ->causedBy(auth()->user())
                ->performedOn($refund)
                ->withProperties([
                    'refund_number' => $refund->refund_number,
                    'refund_amount' => $refund->refund_amount,
                    'admin_notes' => $request->admin_notes
                ])
                ->log('Admin approved refund: ' . $refund->refund_number);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Refund approved successfully!'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to approve refund: ' . $e->getMessage()
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
                'errors' => $validator->errors()
            ], 422);
        }

        if ($refund->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Only pending refunds can be rejected.'
            ], 422);
        }

        try {
            DB::beginTransaction();

            $refund->reject(auth()->id(), $request->admin_notes);

            // Log activity
            activity()
                ->causedBy(auth()->user())
                ->performedOn($refund)
                ->withProperties([
                    'refund_number' => $refund->refund_number,
                    'admin_notes' => $request->admin_notes
                ])
                ->log('Admin rejected refund: ' . $refund->refund_number);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Refund rejected successfully!'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to reject refund: ' . $e->getMessage()
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
                'errors' => $validator->errors()
            ], 422);
        }

        if ($refund->status !== 'approved') {
            return response()->json([
                'success' => false,
                'message' => 'Only approved refunds can be marked as completed.'
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Mark refund as completed
            $refund->markAsCompleted();

            // Update admin notes with transaction reference if provided
            if ($request->filled('transaction_reference')) {
                $refund->update([
                    'admin_notes' => ($refund->admin_notes ?? '') . ' | Transaction Ref: ' . $request->transaction_reference
                ]);
            }

            // Log activity
            activity()
                ->causedBy(auth()->user())
                ->performedOn($refund)
                ->withProperties([
                    'refund_number' => $refund->refund_number,
                    'refund_amount' => $refund->refund_amount,
                    'transaction_reference' => $request->transaction_reference
                ])
                ->log('Admin completed refund: ' . $refund->refund_number);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Refund marked as completed successfully!'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to complete refund: ' . $e->getMessage()
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
                'errors' => $validator->errors()
            ], 422);
        }

        if (!in_array($refund->status, ['pending', 'approved'])) {
            return response()->json([
                'success' => false,
                'message' => 'Only pending or approved refunds can be updated.'
            ], 422);
        }

        try {
            DB::beginTransaction();

            $oldData = $refund->toArray();

            $refund->update([
                'refund_amount' => $request->refund_amount,
                'admin_notes' => $request->admin_notes,
            ]);

            // Log activity
            activity()
                ->causedBy(auth()->user())
                ->performedOn($refund)
                ->withProperties([
                    'old' => $oldData,
                    'new' => $refund->toArray()
                ])
                ->log('Admin updated refund: ' . $refund->refund_number);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Refund updated successfully!',
                'refund' => $refund->load(['user', 'booking'])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update refund: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        $refund = Refund::findOrFail($id);

        // Prevent deletion of processed refunds
        if (in_array($refund->status, ['completed'])) {
            return response()->json([
                'success' => false,
                'message' => 'Completed refunds cannot be deleted.'
            ], 422);
        }

        try {
            DB::beginTransaction();

            $refundNumber = $refund->refund_number;

            // Log activity before deletion
            activity()
                ->causedBy(auth()->user())
                ->withProperties([
                    'refund_number' => $refundNumber,
                    'booking_id' => $refund->booking_id
                ])
                ->log('Admin deleted refund: ' . $refundNumber);

            $refund->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Refund deleted successfully!'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete refund: ' . $e->getMessage()
            ], 500);
        }
    }

    public function exportReport(Request $request)
    {
        $query = Refund::with(['booking.user', 'user']);

        // Apply filters
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

        // Generate CSV
        $filename = 'refunds_report_' . now()->format('Y-m-d_His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($refunds) {
            $file = fopen('php://output', 'w');
            
            // Header row
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

            // Data rows
            foreach ($refunds as $refund) {
                fputcsv($file, [
                    $refund->refund_number,
                    $refund->booking->booking_number,
                    $refund->user->name,
                    $refund->original_amount,
                    $refund->refund_amount,
                    $refund->reason,
                    ucfirst($refund->status),
                    $refund->requested_at->format('Y-m-d H:i:s'),
                    $refund->processed_at ? $refund->processed_at->format('Y-m-d H:i:s') : 'N/A',
                    $refund->processedBy ? $refund->processedBy->name : 'N/A',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}