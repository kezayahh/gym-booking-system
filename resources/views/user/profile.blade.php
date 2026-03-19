{{-- File: resources/views/user/profile.blade.php --}}
@extends('layouts.user')
@section('title', 'My Profile - City Gymnasium')

@section('content')
<div class="mb-6">
    <h1 class="text-3xl font-bold text-gray-800">
        <i class="fas fa-user-circle text-blue-500 mr-2"></i>My Profile
    </h1>
    <p class="text-gray-600 mt-1">Manage your personal information and settings</p>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    
    {{-- Left Column - Profile Picture --}}
    <div class="lg:col-span-1">
        <div class="bg-white rounded-lg shadow-lg p-6">
            <div class="text-center">
                <div class="relative inline-block">
                    <img id="profilePic" 
                         src="{{ $user->profile_picture_url }}" 
                         alt="Profile Picture"
                         class="w-40 h-40 rounded-full object-cover border-4 border-blue-500 shadow-lg">
                    
                    <button onclick="document.getElementById('uploadPhoto').click()"
                            class="absolute bottom-0 right-0 bg-blue-600 text-white rounded-full p-3 hover:bg-blue-700 transition shadow-lg"
                            title="Change Picture">
                        <i class="fas fa-camera"></i>
                    </button>
                </div>
                
                <input type="file" id="uploadPhoto" accept="image/*" class="hidden">
                
                <h3 class="text-xl font-bold text-gray-800 mt-4">{{ $user->name }}</h3>
                <p class="text-gray-600 text-sm">{{ $user->email }}</p>
                
                <div class="mt-4 pt-4 border-t border-gray-200">
                    <p class="text-sm text-gray-600">Member Since</p>
                    <p class="text-sm font-semibold text-gray-800">{{ $user->created_at->format('M d, Y') }}</p>
                </div>

                @if($user->profile_picture)
                <button onclick="deleteProfilePicture()" 
                        class="mt-4 w-full px-4 py-2 bg-red-100 text-red-600 rounded-lg hover:bg-red-200 transition text-sm font-semibold">
                    <i class="fas fa-trash mr-2"></i>Remove Picture
                </button>
                @endif
            </div>
        </div>

        {{-- Account Stats --}}
        <div class="bg-white rounded-lg shadow-lg p-6 mt-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4">
                <i class="fas fa-chart-line text-blue-500 mr-2"></i>Account Stats
            </h3>
            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <span class="text-gray-600 text-sm">Total Bookings</span>
                    <span class="font-bold text-gray-800">{{ $user->bookings()->count() }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-gray-600 text-sm">Completed</span>
                    <span class="font-bold text-green-600">{{ $user->bookings()->completed()->count() }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-gray-600 text-sm">Pending</span>
                    <span class="font-bold text-yellow-600">{{ $user->bookings()->pending()->count() }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Right Column - Profile Information --}}
    <div class="lg:col-span-2">
        {{-- Personal Information --}}
        <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-xl font-bold text-gray-800">
                    <i class="fas fa-user text-blue-500 mr-2"></i>Personal Information
                </h3>
                <button id="editProfileBtn" onclick="toggleEditMode()" 
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-semibold">
                    <i class="fas fa-edit mr-2"></i>Edit Profile
                </button>
            </div>

            <form id="profileForm" class="space-y-4">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-user text-gray-400 mr-1"></i>Full Name
                        </label>
                        <input type="text" id="name" name="name" value="{{ $user->name }}" readonly
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-gray-50">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-envelope text-gray-400 mr-1"></i>Email Address
                        </label>
                        <input type="email" id="email" name="email" value="{{ $user->email }}" readonly
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-gray-50">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-phone text-gray-400 mr-1"></i>Phone Number
                        </label>
                        <input type="text" id="phone" name="phone" value="{{ $user->phone ?? 'Not provided' }}" readonly
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-gray-50">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-map-marker-alt text-gray-400 mr-1"></i>Address
                        </label>
                        <input type="text" id="address" name="address" value="{{ $user->address ?? 'Not provided' }}" readonly
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-gray-50">
                    </div>
                </div>

                <div id="profileButtons" class="hidden flex space-x-3 pt-4">
                    <button type="submit" class="flex-1 px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-semibold shadow-lg">
                        <i class="fas fa-save mr-2"></i>Save Changes
                    </button>
                    <button type="button" onclick="cancelEditMode()" 
                            class="px-6 py-3 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition font-semibold">
                        <i class="fas fa-times mr-2"></i>Cancel
                    </button>
                </div>
            </form>
        </div>

        {{-- Change Password --}}
        <div class="bg-white rounded-lg shadow-lg p-6">
            <h3 class="text-xl font-bold text-gray-800 mb-6">
                <i class="fas fa-lock text-blue-500 mr-2"></i>Change Password
            </h3>

            <form id="passwordForm" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        <i class="fas fa-key text-gray-400 mr-1"></i>Current Password
                    </label>
                    <input type="password" id="current_password" name="current_password" required
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                           placeholder="Enter current password">
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-lock text-gray-400 mr-1"></i>New Password
                        </label>
                        <input type="password" id="new_password" name="new_password" required
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="Enter new password">
                        <p class="text-xs text-gray-500 mt-1">Minimum 8 characters</p>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-lock text-gray-400 mr-1"></i>Confirm Password
                        </label>
                        <input type="password" id="new_password_confirmation" name="new_password_confirmation" required
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="Confirm new password">
                    </div>
                </div>

                <button type="submit" class="w-full px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-semibold shadow-lg">
                    <i class="fas fa-key mr-2"></i>Update Password
                </button>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
let editMode = false;
const originalValues = {
    name: '{{ $user->name }}',
    email: '{{ $user->email }}',
    phone: '{{ $user->phone ?? "" }}',
    address: '{{ $user->address ?? "" }}'
};

// Toggle Edit Mode
function toggleEditMode() {
    editMode = true;
    document.getElementById('name').removeAttribute('readonly');
    document.getElementById('email').removeAttribute('readonly');
    document.getElementById('phone').removeAttribute('readonly');
    document.getElementById('address').removeAttribute('readonly');
    
    document.getElementById('name').classList.remove('bg-gray-50');
    document.getElementById('email').classList.remove('bg-gray-50');
    document.getElementById('phone').classList.remove('bg-gray-50');
    document.getElementById('address').classList.remove('bg-gray-50');
    
    document.getElementById('profileButtons').classList.remove('hidden');
    document.getElementById('editProfileBtn').classList.add('hidden');
}

function cancelEditMode() {
    editMode = false;
    
    // Restore original values
    document.getElementById('name').value = originalValues.name;
    document.getElementById('email').value = originalValues.email;
    document.getElementById('phone').value = originalValues.phone || 'Not provided';
    document.getElementById('address').value = originalValues.address || 'Not provided';
    
    document.getElementById('name').setAttribute('readonly', true);
    document.getElementById('email').setAttribute('readonly', true);
    document.getElementById('phone').setAttribute('readonly', true);
    document.getElementById('address').setAttribute('readonly', true);
    
    document.getElementById('name').classList.add('bg-gray-50');
    document.getElementById('email').classList.add('bg-gray-50');
    document.getElementById('phone').classList.add('bg-gray-50');
    document.getElementById('address').classList.add('bg-gray-50');
    
    document.getElementById('profileButtons').classList.add('hidden');
    document.getElementById('editProfileBtn').classList.remove('hidden');
}

// Update Profile Information
document.getElementById('profileForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    if (!editMode) return;

    const formData = new FormData(e.target);
    const data = Object.fromEntries(formData);

    fetch('/user/profile/update-info', {
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
            alert(result.message);
            location.reload();
        } else {
            alert(result.message || 'An error occurred');
        }
    })
    .catch(error => {
        alert('An error occurred. Please try again.');
        console.error(error);
    });
});

// Update Password
document.getElementById('passwordForm').addEventListener('submit', function(e) {
    e.preventDefault();

    const formData = new FormData(e.target);
    const data = Object.fromEntries(formData);

    fetch('/user/profile/update-password', {
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
            alert(result.message);
            document.getElementById('passwordForm').reset();
        } else {
            alert(result.message || 'An error occurred');
        }
    })
    .catch(error => {
        alert('An error occurred. Please try again.');
        console.error(error);
    });
});

// Upload Profile Picture
document.getElementById('uploadPhoto').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (!file) return;

    const formData = new FormData();
    formData.append('photo', file);

    fetch('/user/profile/upload-photo', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: formData
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            alert(result.message);
            document.getElementById('profilePic').src = result.photo_url;
            setTimeout(() => location.reload(), 1000);
        } else {
            alert(result.message || 'An error occurred');
        }
    })
    .catch(error => {
        alert('An error occurred. Please try again.');
        console.error(error);
    });
});

// Delete Profile Picture
function deleteProfilePicture() {
    if (!confirm('Are you sure you want to remove your profile picture?')) return;

    fetch('/user/profile/delete-photo', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            alert(result.message);
            location.reload();
        } else {
            alert(result.message || 'No profile picture to delete');
        }
    })
    .catch(error => {
        alert('An error occurred. Please try again.');
        console.error(error);
    });
}
</script>
@endpush