<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    /**
     * Display admin profile
     */
    public function index()
    {
        return view('admin.profile');
    }

    /**
     * Update admin profile information
     */
    public function updateInfo(Request $request)
    {
        $user = Auth::user();
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
        ]);

        $user->update($validated);

        // Log activity
        activity()
            ->causedBy($user)
            ->performedOn($user)
            ->withProperties(['updated_fields' => array_keys($validated)])
            ->log('Admin updated their profile information');

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully!'
        ]);
    }

    /**
     * Update admin password
     */
    public function updatePassword(Request $request)
    {
        $user = Auth::user();
        
        $validated = $request->validate([
            'current_password' => 'required',
            'new_password' => ['required', 'confirmed', Password::min(8)],
        ]);

        // Check if current password is correct
        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Current password is incorrect!'
            ], 422);
        }

        // Update password
        $user->update([
            'password' => Hash::make($request->new_password)
        ]);

        // Log activity
        activity()
            ->causedBy($user)
            ->performedOn($user)
            ->log('Admin changed their password');

        return response()->json([
            'success' => true,
            'message' => 'Password updated successfully!'
        ]);
    }

    /**
     * Upload profile picture
     */
    public function uploadPhoto(Request $request)
    {
        $user = Auth::user();
        
        $request->validate([
            'photo' => 'required|image|mimes:jpeg,png,jpg|max:2048', // Max 2MB
        ]);

        // Delete old photo if exists
        if ($user->profile_picture && Storage::disk('public')->exists($user->profile_picture)) {
            Storage::disk('public')->delete($user->profile_picture);
        }

        // Store new photo
        $path = $request->file('photo')->store('profile-pictures', 'public');
        
        $user->update([
            'profile_picture' => $path
        ]);

        // Log activity
        activity()
            ->causedBy($user)
            ->performedOn($user)
            ->log('Admin uploaded a new profile picture');

        return response()->json([
            'success' => true,
            'message' => 'Profile picture updated successfully!',
            'photo_url' => asset('storage/' . $path)
        ]);
    }

    /**
     * Delete profile picture
     */
    public function deletePhoto()
    {
        $user = Auth::user();
        
        if ($user->profile_picture && Storage::disk('public')->exists($user->profile_picture)) {
            Storage::disk('public')->delete($user->profile_picture);
            
            $user->update([
                'profile_picture' => null
            ]);

            // Log activity
            activity()
                ->causedBy($user)
                ->performedOn($user)
                ->log('Admin removed their profile picture');

            return response()->json([
                'success' => true,
                'message' => 'Profile picture deleted successfully!'
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'No profile picture to delete!'
        ], 404);
    }
}