<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function markAllRead(Request $request)
    {
        Notification::query()
            ->where('user_id', auth()->id())
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return back()->with('success', 'Notifications marked as read.');
    }
}
