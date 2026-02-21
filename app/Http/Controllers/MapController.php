<?php

namespace App\Http\Controllers;

use App\Models\Connection;
use App\Models\LocationTag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MapController extends Controller
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

        $tags = LocationTag::query()
            ->with('user:id,name,slug,hide_tags_until')
            ->whereIn('user_id', $visibleUserIds)
            ->where('expires_at', '>=', now())
            ->whereHas('user', function ($query) {
                $query->whereNull('hide_tags_until')
                    ->orWhere('hide_tags_until', '<=', now());
            })
            ->latest()
            ->get();

        $myTags = LocationTag::query()
            ->where('user_id', $userId)
            ->where('expires_at', '>=', now())
            ->latest()
            ->get();

        return view('map.index', compact('tags', 'myTags'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'nullable|string|max:120',
            'note' => 'nullable|string|max:255',
            'lat' => 'required|numeric|between:-90,90',
            'lng' => 'required|numeric|between:-180,180',
            'duration' => 'required|in:1,6,24',
        ]);

        $duration = (int) $request->duration;

        LocationTag::create([
            'user_id' => Auth::id(),
            'title' => $request->title,
            'note' => $request->note,
            'lat' => $request->lat,
            'lng' => $request->lng,
            'expires_at' => now()->addHours($duration),
        ]);

        return back()->with('success', 'Location tag shared for 24 hours.');
    }

    public function destroy(LocationTag $tag)
    {
        if ((int) $tag->user_id !== (int) Auth::id()) {
            abort(403);
        }

        $tag->delete();

        return back()->with('success', 'Location tag deleted.');
    }

    public function destroyAll()
    {
        LocationTag::query()
            ->where('user_id', Auth::id())
            ->delete();

        return back()->with('success', 'All your location tags have been deleted.');
    }
}
