<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class UserManagementController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query();

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        // Filter by role
        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $users = $query->latest()->paginate(10);
        
        // Statistics
        $totalUsers = User::where('role', 'user')->count();
        $activeUsers = User::where('role', 'user')->where('status', 'active')->count();
        $suspendedUsers = User::where('role', 'user')->where('status', 'suspended')->count();
        $totalAdmins = User::where('role', 'admin')->count();

        return view('admin.user-management', compact(
            'users', 'totalUsers', 'activeUsers', 'suspendedUsers', 'totalAdmins'
        ));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'date_of_birth' => 'nullable|date',
            'gender' => 'nullable|in:male,female,other',
            'role' => 'required|in:user,admin',
            'status' => 'required|in:active,suspended',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'phone' => $request->phone,
                'address' => $request->address,
                'date_of_birth' => $request->date_of_birth,
                'gender' => $request->gender,
                'role' => $request->role,
                'status' => $request->status,
            ]);

            // Log activity
            activity()
                ->causedBy(auth()->user())
                ->performedOn($user)
                ->withProperties(['user_id' => $user->id, 'role' => $user->role])
                ->log('Created new user: ' . $user->name);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'User created successfully!',
                'user' => $user
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create user: ' . $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('users')->ignore($user->id)],
            'password' => 'nullable|string|min:8|confirmed',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'date_of_birth' => 'nullable|date',
            'gender' => 'nullable|in:male,female,other',
            'role' => 'required|in:user,admin',
            'status' => 'required|in:active,suspended',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $oldData = $user->toArray();

            $updateData = [
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'address' => $request->address,
                'date_of_birth' => $request->date_of_birth,
                'gender' => $request->gender,
                'role' => $request->role,
                'status' => $request->status,
            ];

            if ($request->filled('password')) {
                $updateData['password'] = Hash::make($request->password);
            }

            $user->update($updateData);

            // Log activity
            activity()
                ->causedBy(auth()->user())
                ->performedOn($user)
                ->withProperties(['old' => $oldData, 'new' => $user->toArray()])
                ->log('Updated user: ' . $user->name);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'User updated successfully!',
                'user' => $user
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update user: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
{
    $user = User::findOrFail($id);

    // Prevent deleting self
    if ($user->id === auth()->id()) {
        return response()->json([
            'success' => false,
            'message' => 'You cannot delete your own account!'
        ], 403);
    }

    try {
        DB::beginTransaction();

        $userName = $user->name;

        // Log activity before deletion
        activity()
            ->causedBy(auth()->user())
            ->withProperties(['user_id' => $user->id, 'name' => $userName])
            ->log('Deleted user: ' . $userName);

        $user->delete(); // 👈 THIS is now a SOFT delete because of SoftDeletes

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'User deleted successfully!'
        ]);

    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json([
            'success' => false,
            'message' => 'Failed to delete user: ' . $e->getMessage()
        ], 500);
    }
}


    public function toggleStatus($id)
    {
        $user = User::findOrFail($id);

        // Prevent suspending self
        if ($user->id === auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot change your own status!'
            ], 403);
        }

        try {
            DB::beginTransaction();

            $newStatus = $user->status === 'active' ? 'suspended' : 'active';
            $user->update(['status' => $newStatus]);

            // Log activity
            activity()
                ->causedBy(auth()->user())
                ->performedOn($user)
                ->withProperties(['old_status' => $user->status, 'new_status' => $newStatus])
                ->log(ucfirst($newStatus) . ' user: ' . $user->name);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "User {$newStatus} successfully!",
                'status' => $newStatus
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update status: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        $user = User::with(['bookings', 'payments', 'refunds'])
            ->withCount(['bookings', 'payments'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'user' => $user
        ]);
    }
}