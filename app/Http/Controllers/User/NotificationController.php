<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{

    public function index(Request $request)
    {
        $user = Auth::user();

        $notifications = Notification::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        $totalCount = Notification::where('user_id', $user->id)->count();
        $unreadCount = Notification::where('user_id', $user->id)
            ->where('is_read', false)
            ->count();

        $items = collect($notifications->items())->map(function ($n) {
            return [
                'id' => $n->id,
                'title' => $n->title,
                'message' => $n->message,
                'type' => $n->type,
                'is_read' => (bool)$n->is_read,
                'created_at' => $n->created_at->diffForHumans(),
            ];
        });

        return response()->json([
            'stats' => [
                'totalCount' => $totalCount,
                'unreadCount' => $unreadCount,
            ],
            'notifications' => [
                'data' => $items,
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(),
            ]
        ]);
    }


    public function markAsRead($id)
    {
        $notification = Notification::where('user_id', Auth::id())
            ->where('id', $id)
            ->first();

        if (!$notification) {
            return response()->json([
                'success' => false,
                'message' => 'Notification not found'
            ]);
        }

        $notification->update([
            'is_read' => true,
            'read_at' => now()
        ]);

        return response()->json([
            'success' => true
        ]);
    }


    public function markAllAsRead()
    {
        Notification::where('user_id', Auth::id())
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now()
            ]);

        return response()->json([
            'success' => true
        ]);
    }


    public function delete($id)
    {
        Notification::where('user_id', Auth::id())
            ->where('id', $id)
            ->delete();

        return response()->json([
            'success' => true
        ]);
    }


    public function deleteAll()
    {
        Notification::where('user_id', Auth::id())->delete();

        return response()->json([
            'success' => true
        ]);
    }
}