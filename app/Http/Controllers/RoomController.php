<?php

namespace App\Http\Controllers;

use App\Models\Room;
use App\Models\RoomMember;
use App\Models\RoomMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RoomController extends Controller
{
    public function index()
    {
        $rooms = Room::query()
            ->withCount('members')
            ->latest()
            ->get();

        return view('rooms.index', compact('rooms'));
    }

    public function create()
    {
        return view('rooms.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
        ]);

        $room = Room::create([
            'title' => $request->title,
            'description' => $request->description,
            'created_by' => Auth::id(),
        ]);

        RoomMember::firstOrCreate([
            'room_id' => $room->id,
            'user_id' => Auth::id(),
        ], [
            'role' => 'owner',
        ]);

        return redirect()->route('rooms.show', $room->id)->with('success', 'Room created.');
    }

    public function show(Room $room)
    {
        $messages = RoomMessage::query()
            ->where('room_id', $room->id)
            ->with('user:id,name')
            ->latest()
            ->limit(100)
            ->get()
            ->reverse()
            ->values();

        $isMember = RoomMember::query()
            ->where('room_id', $room->id)
            ->where('user_id', Auth::id())
            ->exists();

        return view('rooms.show', compact('room', 'messages', 'isMember'));
    }

    public function join(Room $room)
    {
        RoomMember::firstOrCreate([
            'room_id' => $room->id,
            'user_id' => Auth::id(),
        ]);

        return back()->with('success', 'Joined the room.');
    }

    public function postMessage(Request $request, Room $room)
    {
        $request->validate([
            'message' => 'required|string|max:2000',
        ]);

        $isMember = RoomMember::query()
            ->where('room_id', $room->id)
            ->where('user_id', Auth::id())
            ->exists();

        if (!$isMember) {
            return back()->with('error', 'Join the room to post.');
        }

        RoomMessage::create([
            'room_id' => $room->id,
            'user_id' => Auth::id(),
            'message' => $request->message,
        ]);

        return back();
    }
}
