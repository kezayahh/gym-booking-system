@extends('layouts.user')

@section('title', 'Notifications - City Gymnasium')

@section('content')
<div x-data="notificationManager()">
    <!-- Page Header -->
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-800">
            <i class="fas fa-bell text-yellow-500 mr-2"></i>Notifications
        </h1>
        <p class="text-gray-600 mt-1">Stay updated with your booking activities</p>
    </div>

    <!-- Statistics & Actions -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div class="flex items-center space-x-6">
                <div>
                    <p class="text-sm text-gray-600">Total Notifications</p>
                    <p class="text-2xl font-bold text-gray-800">{{ $totalCount }}</p>
                </div>
                <div class="border-l border-gray-300 pl-6">
                    <p class="text-sm text-gray-600">Unread</p>
                    <p class="text-2xl font-bold text-yellow-600">{{ $unreadCount }}</p>
                </div>
            </div>

            <div class="flex items-center space-x-3">
                <button 
                    @click="markAllAsRead()"
                    :disabled="loading"
                    class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition text-sm disabled:opacity-50">
                    <i class="fas fa-check-double mr-2"></i>Mark All Read
                </button>
                <button 
                    @click="deleteAll()"
                    :disabled="loading"
                    class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition text-sm disabled:opacity-50">
                    <i class="fas fa-trash mr-2"></i>Clear All
                </button>
            </div>
        </div>
    </div>

    <!-- Filter Tabs -->
    <div class="bg-white rounded-lg shadow mb-6">
        <div class="border-b border-gray-200">
            <nav class="flex space-x-4 px-6" aria-label="Tabs">
                <button 
                    @click="filterType = 'all'" 
                    :class="filterType === 'all' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                    class="py-4 px-1 border-b-2 font-medium text-sm transition">
                    All
                </button>
                <button 
                    @click="filterType = 'unread'" 
                    :class="filterType === 'unread' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                    class="py-4 px-1 border-b-2 font-medium text-sm transition">
                    Unread
                    @if($unreadCount > 0)
                        <span class="ml-2 bg-yellow-100 text-yellow-800 py-0.5 px-2 rounded-full text-xs">{{ $unreadCount }}</span>
                    @endif
                </button>
                <button 
                    @click="filterType = 'read'" 
                    :class="filterType === 'read' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                    class="py-4 px-1 border-b-2 font-medium text-sm transition">
                    Read
                </button>
            </nav>
        </div>
    </div>

    <!-- Notifications List -->
    <div class="space-y-3">
        @forelse($notifications as $notification)
        <div 
            x-show="showNotification({{ $notification->is_read ? 'true' : 'false' }})"
            class="bg-white rounded-lg shadow hover:shadow-lg transition {{ !$notification->is_read ? 'border-l-4 border-yellow-500' : '' }}">
            <div class="p-5">
                <div class="flex items-start justify-between">
                    <div class="flex items-start space-x-4 flex-1">
                        <!-- Icon -->
                        <div class="flex-shrink-0">
                            <div class="w-12 h-12 rounded-full flex items-center justify-center
                                {{ $notification->type === 'booking_confirmed' ? 'bg-green-100' : 
                                   ($notification->type === 'payment_received' ? 'bg-blue-100' : 
                                   ($notification->type === 'booking_cancelled' ? 'bg-red-100' : 
                                   ($notification->type === 'refund_approved' ? 'bg-purple-100' : 'bg-gray-100'))) }}">
                                <i class="fas 
                                    {{ $notification->type === 'booking_confirmed' ? 'fa-check-circle text-green-600' : 
                                       ($notification->type === 'payment_received' ? 'fa-dollar-sign text-blue-600' : 
                                       ($notification->type === 'booking_cancelled' ? 'fa-times-circle text-red-600' : 
                                       ($notification->type === 'refund_approved' ? 'fa-undo text-purple-600' : 'fa-bell text-gray-600'))) }} text-xl">
                                </i>
                            </div>
                        </div>

                        <!-- Content -->
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 mb-1">
                                <h4 class="text-base font-semibold text-gray-800">{{ $notification->title }}</h4>
                                @if(!$notification->is_read)
                                    <span class="px-2 py-1 bg-yellow-100 text-yellow-800 text-xs font-semibold rounded-full">New</span>
                                @endif
                            </div>
                            <p class="text-sm text-gray-600 mb-2">{{ $notification->message }}</p>
                            <div class="flex items-center text-xs text-gray-500">
                                <i class="far fa-clock mr-1"></i>
                                <span>{{ $notification->created_at->diffForHumans() }}</span>
                                <span class="mx-2">•</span>
                                <span class="capitalize">{{ str_replace('_', ' ', $notification->type) }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center space-x-2 ml-4">
                        @if(!$notification->is_read)
                            <button 
                                @click="markAsRead({{ $notification->id }})"
                                class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition"
                                title="Mark as read">
                                <i class="fas fa-check"></i>
                            </button>
                        @endif
                        <button 
                            @click="deleteNotification({{ $notification->id }})"
                            class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition"
                            title="Delete">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        @empty
        <div class="bg-white rounded-lg shadow p-12 text-center">
            <i class="fas fa-bell-slash text-6xl text-gray-300 mb-4"></i>
            <p class="text-gray-600 text-lg font-semibold">No notifications</p>
            <p class="text-gray-500 text-sm mt-2">You're all caught up!</p>
        </div>
        @endforelse
    </div>

    <!-- Pagination -->
    @if($notifications->hasPages())
    <div class="mt-6">
        {{ $notifications->links() }}
    </div>
    @endif
</div>

<script>
function notificationManager() {
    return {
        filterType: 'all',
        loading: false,

        showNotification(isRead) {
            if (this.filterType === 'all') return true;
            if (this.filterType === 'unread') return !isRead;
            if (this.filterType === 'read') return isRead;
            return true;
        },

        async markAsRead(notificationId) {
            try {
                const response = await fetch(`/user/notifications/${notificationId}/read`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });

                const data = await response.json();

                if (data.success) {
                    window.location.reload();
                } else {
                    alert('Failed to mark as read');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('An error occurred');
            }
        },

        async markAllAsRead() {
            if (!confirm('Mark all notifications as read?')) return;

            this.loading = true;

            try {
                const response = await fetch('/user/notifications/mark-all-read', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });

                const data = await response.json();

                if (data.success) {
                    alert(data.message);
                    window.location.reload();
                } else {
                    alert('Failed to mark all as read');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('An error occurred');
            } finally {
                this.loading = false;
            }
        },

        async deleteNotification(notificationId) {
            if (!confirm('Delete this notification?')) return;

            try {
                const response = await fetch(`/user/notifications/${notificationId}/delete`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });

                const data = await response.json();

                if (data.success) {
                    window.location.reload();
                } else {
                    alert('Failed to delete notification');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('An error occurred');
            }
        },

        async deleteAll() {
            if (!confirm('Delete all notifications? This action cannot be undone.')) return;

            this.loading = true;

            try {
                const response = await fetch('/user/notifications/delete-all', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });

                const data = await response.json();

                if (data.success) {
                    alert(data.message);
                    window.location.reload();
                } else {
                    alert('Failed to delete all notifications');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('An error occurred');
            } finally {
                this.loading = false;
            }
        }
    }
}
</script>

<style>
[x-cloak] { display: none !important; }
</style>
@endsection