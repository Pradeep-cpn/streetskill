<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Connection;
use Illuminate\Support\Facades\Auth;

class FeedController extends Controller
{
    public function index()
    {
        $userId = Auth::id();

        $friendIds = Connection::query()
            ->where('status', 'accepted')
            ->where(function ($query) use ($userId) {
                $query->where('requester_id', $userId)
                    ->orWhere('addressee_id', $userId);
            })
            ->get()
            ->flatMap(function (Connection $connection) use ($userId) {
                return [(int) $connection->requester_id === (int) $userId ? (int) $connection->addressee_id : (int) $connection->requester_id];
            })
            ->unique()
            ->values()
            ->all();

        $visibleUserIds = array_merge([$userId], $friendIds);

        $activity = ActivityLog::query()
            ->with('user:id,name,slug')
            ->whereIn('user_id', $visibleUserIds)
            ->latest()
            ->limit(40)
            ->get();

        return view('feed.index', compact('activity'));
    }
}
