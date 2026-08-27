<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index()
    {
        $notifications = Notification::query()
            ->where('user_id', auth()->id())
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();

        return response()->json($notifications);
    }

    public function unreadCount()
    {
        $count = Notification::query()
            ->where('user_id', auth()->id())
            ->where('is_read', false)
            ->count();

        return response()->json(['count' => $count]);
    }

    public function markRead(Request $request, string $id)
    {
        Notification::query()
            ->where('id', $id)
            ->where('user_id', auth()->id())
            ->update(['is_read' => true]);

        return response()->json(['success' => true]);
    }

    public function markAllRead()
    {
        Notification::query()
            ->where('user_id', auth()->id())
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return response()->json(['success' => true]);
    }

    public function destroy(string $id)
    {
        Notification::query()
            ->where('id', $id)
            ->where('user_id', auth()->id())
            ->delete();

        return response()->json(['success' => true]);
    }

    public function clearAll()
    {
        Notification::query()->where('user_id', auth()->id())->delete();

        return response()->json(['success' => true]);
    }
}
