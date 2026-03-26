<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $activity = [
            'totalActions' => ActivityLog::where('user_id', $user->id)->count(),
            'thisMonth' => ActivityLog::where('user_id', $user->id)
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count(),
            'lastLogin' => $user->updated_at ? $user->updated_at->diffForHumans() : 'N/A',
        ];

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'address' => $user->address,
                'role' => $user->role,
                'profile_picture' => $user->profile_picture,
                'profile_picture_url' => $user->profile_picture_url,
                'created_at' => $user->created_at,
            ],
            'activity' => $activity,
        ]);
    }

    public function updateInfo(Request $request)
    {
        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:500'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user->update([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'address' => $request->address,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully.',
            'user' => $user->fresh(),
        ]);
    }

    public function updatePassword(Request $request)
    {
        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'current_password' => ['required'],
            'new_password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'new_password.confirmed' => 'The new password confirmation does not match.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Current password is incorrect.',
            ], 422);
        }

        $user->update([
            'password' => Hash::make($request->new_password),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Password updated successfully.',
        ]);
    }

    public function uploadPhoto(Request $request)
    {
        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid photo upload.',
                'errors' => $validator->errors(),
            ], 422);
        }

        if ($user->profile_picture && Storage::disk('public')->exists($user->profile_picture)) {
            Storage::disk('public')->delete($user->profile_picture);
        }

        $path = $request->file('photo')->store('profile-pictures', 'public');

        $user->update([
            'profile_picture' => $path,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Profile picture uploaded successfully.',
            'profile_picture_url' => $user->fresh()->profile_picture_url,
        ]);
    }

    public function deletePhoto()
    {
        $user = Auth::user();

        if (!$user->profile_picture) {
            return response()->json([
                'success' => false,
                'message' => 'No profile picture to delete.',
            ], 422);
        }

        if (Storage::disk('public')->exists($user->profile_picture)) {
            Storage::disk('public')->delete($user->profile_picture);
        }

        $user->update([
            'profile_picture' => null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Profile picture removed successfully.',
        ]);
    }
}